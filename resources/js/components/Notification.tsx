import React, { createContext, useContext, useState, useCallback, useEffect } from 'react'
import { createPortal } from 'react-dom'
import { X, CheckCircle, AlertCircle, AlertTriangle, Info } from 'lucide-react'
import { Button } from '@/components/ui/button'

// Types
export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface Toast {
  id: string
  type: ToastType
  title: string
  description?: string
  duration?: number
  action?: {
    label: string
    onClick: () => void
  }
}

interface ToastContextType {
  toasts: Toast[]
  addToast: (toast: Omit<Toast, 'id'>) => void
  removeToast: (id: string) => void
  success: (title: string, description?: string) => void
  error: (title: string, description?: string) => void
  warning: (title: string, description?: string) => void
  info: (title: string, description?: string) => void
}

// Context
const ToastContext = createContext<ToastContextType | undefined>(undefined)

// Hook
export function useToast() {
  const context = useContext(ToastContext)
  if (!context) {
    throw new Error('useToast must be used within ToastProvider')
  }
  return context
}

// Provider
export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])

  const generateId = () => Math.random().toString(36).substr(2, 9)

  const addToast = useCallback((toast: Omit<Toast, 'id'>) => {
    const id = generateId()
    const newToast: Toast = {
      id,
      duration: 5000,
      ...toast,
    }

    setToasts(prev => [...prev, newToast])

    // Auto remove after duration
    if (newToast.duration && newToast.duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, newToast.duration)
    }
  }, [])

  const removeToast = useCallback((id: string) => {
    setToasts(prev => prev.filter(toast => toast.id !== id))
  }, [])

  const success = useCallback((title: string, description?: string) => {
    addToast({ type: 'success', title, description })
  }, [addToast])

  const error = useCallback((title: string, description?: string) => {
    addToast({ type: 'error', title, description, duration: 7000 })
  }, [addToast])

  const warning = useCallback((title: string, description?: string) => {
    addToast({ type: 'warning', title, description })
  }, [addToast])

  const info = useCallback((title: string, description?: string) => {
    addToast({ type: 'info', title, description })
  }, [addToast])

  return (
    <ToastContext.Provider value={{
      toasts,
      addToast,
      removeToast,
      success,
      error,
      warning,
      info
    }}>
      {children}
      <ToastContainer toasts={toasts} removeToast={removeToast} />
    </ToastContext.Provider>
  )
}

// Toast Container
function ToastContainer({ 
  toasts, 
  removeToast 
}: { 
  toasts: Toast[]
  removeToast: (id: string) => void 
}) {
  const [mounted, setMounted] = useState(false)

  useEffect(() => {
    setMounted(true)
  }, [])

  if (!mounted || typeof window === 'undefined') return null

  return createPortal(
    <div className="fixed top-4 right-4 z-[100] flex flex-col gap-2 w-full max-w-sm sm:max-w-md">
      {toasts.map((toast) => (
        <ToastItem 
          key={toast.id} 
          toast={toast} 
          onRemove={() => removeToast(toast.id)} 
        />
      ))}
    </div>,
    document.body
  )
}

// Toast Item
function ToastItem({ 
  toast, 
  onRemove 
}: { 
  toast: Toast
  onRemove: () => void 
}) {
  const [isVisible, setIsVisible] = useState(false)
  const [isRemoving, setIsRemoving] = useState(false)

  useEffect(() => {
    // Animate in
    const timer = setTimeout(() => setIsVisible(true), 10)
    return () => clearTimeout(timer)
  }, [])

  const handleRemove = () => {
    setIsRemoving(true)
    setTimeout(() => onRemove(), 150)
  }

  const getIcon = () => {
    const iconProps = "h-4 w-4 sm:h-5 sm:w-5 flex-shrink-0"
    
    switch (toast.type) {
      case 'success':
        return <CheckCircle className={`${iconProps} text-green-500`} />
      case 'error':
        return <AlertCircle className={`${iconProps} text-red-500`} />
      case 'warning':
        return <AlertTriangle className={`${iconProps} text-yellow-500`} />
      case 'info':
        return <Info className={`${iconProps} text-blue-500`} />
      default:
        return <Info className={`${iconProps} text-blue-500`} />
    }
  }

  const getStyles = () => {
    const base = "bg-background border shadow-lg rounded-lg p-3 sm:p-4 transition-all duration-300 ease-out"
    
    const variants = {
      success: "border-green-200 dark:border-green-800",
      error: "border-red-200 dark:border-red-800", 
      warning: "border-yellow-200 dark:border-yellow-800",
      info: "border-blue-200 dark:border-blue-800"
    }

    const animation = isRemoving 
      ? "opacity-0 scale-95 translate-x-full" 
      : isVisible 
        ? "opacity-100 scale-100 translate-x-0" 
        : "opacity-0 scale-95 translate-x-full"

    return `${base} ${variants[toast.type]} ${animation}`
  }

  return (
    <div className={getStyles()}>
      <div className="flex items-start gap-3">
        {getIcon()}
        
        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between gap-2">
            <div className="flex-1 min-w-0">
              <h4 className="text-sm sm:text-base font-medium text-foreground leading-tight">
                {toast.title}
              </h4>
              
              {toast.description && (
                <p className="text-xs sm:text-sm text-muted-foreground mt-1 leading-relaxed">
                  {toast.description}
                </p>
              )}
            </div>

            <Button
              variant="ghost"
              size="icon"
              className="h-6 w-6 sm:h-8 sm:w-8 flex-shrink-0 -mt-1 -mr-1"
              onClick={handleRemove}
              aria-label="Fermer la notification"
            >
              <X className="h-3 w-3 sm:h-4 sm:w-4" />
            </Button>
          </div>

          {toast.action && (
            <div className="mt-3 flex justify-end">
              <Button
                variant="outline"
                size="sm"
                className="h-7 px-3 text-xs"
                onClick={() => {
                  toast.action?.onClick()
                  handleRemove()
                }}
              >
                {toast.action.label}
              </Button>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}