<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Afficher le panier
     */
    public function index()
    {
        $cart = $this->cartService->getCart();
        $totals = $this->cartService->calculateTotals($cart);
        
        return Inertia::render('Cart/Index', [
            'cart' => $cart,
            'totals' => $totals
        ]);
    }

    /**
     * Ajouter un produit au panier
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_uuid' => 'required|string|exists:products,uuid',
            'quantity' => 'integer|min:1|max:99',
            'variants' => 'array'
        ]);

        $quantity = $request->input('quantity', 1);
        $variants = $request->input('variants', []);

        // Valider le stock
        if (!$this->cartService->validateStock($request->product_uuid, $quantity)) {
            return back()->withErrors(['stock' => 'Stock insuffisant pour ce produit']);
        }

        try {
            $cart = $this->cartService->addItem(
                $request->product_uuid,
                $quantity,
                $variants
            );

            // Retourner les données Inertia avec le nouveau compteur
            return redirect()->back()->with('success', 'Produit ajouté au panier');
        } catch (\Exception $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }
    }

    /**
     * Mettre à jour la quantité d'un produit
     */
    public function update(Request $request, string $productUuid)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:99'
        ]);

        try {
            $cart = $this->cartService->updateItem(
                $productUuid,
                $request->quantity
            );

            return back()->with([
                'success' => 'Panier mis à jour',
                'cart' => $cart
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }
    }

    /**
     * Supprimer un produit du panier
     */
    public function destroy(string $productUuid)
    {
        try {
            $cart = $this->cartService->removeItem($productUuid);
            
            return back()->with([
                'success' => 'Produit retiré du panier',
                'cart' => $cart
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }
    }

    /**
     * Vider le panier
     */
    public function clear()
    {
        try {
            $cart = $this->cartService->clearCart();
            
            return back()->with('success', 'Panier vidé');
        } catch (\Exception $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }
    }

    /**
     * Obtenir le nombre d'articles dans le panier (pour le header)
     */
    public function count()
    {
        $count = $this->cartService->getCartCount();
        
        
        return response()->json(['count' => $count]);
    }
}
