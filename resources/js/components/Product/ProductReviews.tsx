import React, { useState } from 'react'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Star, ThumbsUp, ChevronDown, ChevronUp } from 'lucide-react'
import { cn } from '@/lib/utils'

interface Review {
  id: string
  user_name: string
  rating: number
  title: string
  comment: string
  date: string
  verified_purchase: boolean
  helpful_count: number
}

interface ProductReviewsProps {
  reviews: Review[]
  averageRating?: number
  reviewsCount?: number
}

export default function ProductReviews({ reviews, averageRating, reviewsCount }: ProductReviewsProps) {
  const [showAllReviews, setShowAllReviews] = useState(false)
  const [sortBy, setSortBy] = useState<'recent' | 'helpful' | 'rating'>('recent')

  if (!reviews || reviews.length === 0) return null

  const displayReviews = showAllReviews ? reviews : reviews.slice(0, 3)

  const sortedReviews = [...displayReviews].sort((a, b) => {
    switch (sortBy) {
      case 'helpful':
        return b.helpful_count - a.helpful_count
      case 'rating':
        return b.rating - a.rating
      case 'recent':
      default:
        return new Date(b.date).getTime() - new Date(a.date).getTime()
    }
  })

  const ratingDistribution = [5, 4, 3, 2, 1].map(rating => ({
    rating,
    count: reviews.filter(review => review.rating === rating).length,
    percentage: (reviews.filter(review => review.rating === rating).length / reviews.length) * 100
  }))

  return (
    <section className="mt-16">
      <div className="grid lg:grid-cols-3 gap-8">
        {/* Rating Summary */}
        <div className="space-y-6">
          <h2 className="text-2xl font-bold">Avis clients</h2>
          
          {averageRating && (
            <div className="space-y-4">
              <div className="flex items-center gap-4">
                <span className="text-4xl font-bold">{averageRating?.toFixed(1) || '0.0'}</span>
                <div className="space-y-1">
                  <div className="flex items-center">
                    {[...Array(5)].map((_, i) => (
                      <Star 
                        key={`rating-star-${i}`}
                        className={cn(
                          "h-5 w-5",
                          i < Math.floor(averageRating) 
                            ? "fill-yellow-400 text-yellow-400" 
                            : "text-muted-foreground"
                        )}
                      />
                    ))}
                  </div>
                  <p className="text-sm text-muted-foreground">
                    {reviewsCount} avis clients
                  </p>
                </div>
              </div>

              {/* Rating Distribution */}
              <div className="space-y-2">
                {ratingDistribution.map(({ rating, count, percentage }) => (
                  <div key={rating} className="flex items-center gap-2 text-sm">
                    <span className="w-8">{rating}★</span>
                    <div className="flex-1 bg-muted rounded-full h-2 overflow-hidden">
                      <div 
                        className="h-full bg-yellow-400 transition-all duration-300"
                        style={{ width: `${percentage}%` }}
                      />
                    </div>
                    <span className="w-8 text-muted-foreground">{count}</span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Reviews List */}
        <div className="lg:col-span-2 space-y-6">
          {/* Sort Controls */}
          <div className="flex items-center gap-4">
            <span className="text-sm font-medium">Trier par:</span>
            <div className="flex gap-2">
              {[
                { key: 'recent', label: 'Plus récents' },
                { key: 'helpful', label: 'Plus utiles' },
                { key: 'rating', label: 'Note' }
              ].map(({ key, label }) => (
                <Button
                  key={key}
                  variant={sortBy === key ? "default" : "outline"}
                  size="sm"
                  onClick={() => setSortBy(key as any)}
                >
                  {label}
                </Button>
              ))}
            </div>
          </div>

          {/* Reviews */}
          <div className="space-y-4">
            {sortedReviews.map((review) => (
              <Card key={review.id}>
                <CardContent className="p-6">
                  <div className="space-y-4">
                    {/* Review Header */}
                    <div className="flex items-start justify-between">
                      <div className="space-y-2">
                        <div className="flex items-center gap-2">
                          <span className="font-medium">{review.user_name}</span>
                          {review.verified_purchase && (
                            <Badge variant="secondary" className="text-xs">
                              Achat vérifié
                            </Badge>
                          )}
                        </div>
                        <div className="flex items-center gap-2">
                          <div className="flex items-center">
                            {[...Array(5)].map((_, i) => (
                              <Star 
                                key={`review-${review.id}-star-${i}`}
                                className={cn(
                                  "h-4 w-4",
                                  i < review.rating 
                                    ? "fill-yellow-400 text-yellow-400" 
                                    : "text-muted-foreground"
                                )}
                              />
                            ))}
                          </div>
                          <span className="text-sm text-muted-foreground">
                            {new Date(review.date).toLocaleDateString('fr-FR')}
                          </span>
                        </div>
                      </div>
                    </div>

                    {/* Review Content */}
                    <div className="space-y-2">
                      {review.title && (
                        <h4 className="font-medium">{review.title}</h4>
                      )}
                      <p className="text-muted-foreground leading-relaxed">
                        {review.comment}
                      </p>
                    </div>

                    {/* Review Actions */}
                    <div className="flex items-center justify-between pt-2 border-t">
                      <Button variant="ghost" size="sm" className="text-muted-foreground">
                        <ThumbsUp className="h-4 w-4 mr-1" />
                        Utile ({review.helpful_count})
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>

          {/* Show More Button */}
          {reviews.length > 3 && (
            <div className="text-center">
              <Button
                variant="outline"
                onClick={() => setShowAllReviews(prev => !prev)}
                className="w-full sm:w-auto"
              >
                {showAllReviews ? (
                  <>
                    <ChevronUp className="h-4 w-4 mr-2" />
                    Voir moins d'avis
                  </>
                ) : (
                  <>
                    <ChevronDown className="h-4 w-4 mr-2" />
                    Voir tous les avis ({reviews.length})
                  </>
                )}
              </Button>
            </div>
          )}
        </div>
      </div>
    </section>
  )
}