<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Promotion;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $categories = ProductCategory::active()
            ->withCount(['products' => fn ($query) => $query->visibleToCustomers()])
            ->limit(6)
            ->get();

        $featuredProducts = Product::visibleToCustomers()
            ->featured()
            ->with(['category', 'brand', 'images'])
            ->latest()
            ->limit(4)
            ->get();

        $latestProducts = Product::visibleToCustomers()
            ->with(['category', 'brand', 'images'])
            ->latest()
            ->limit(8)
            ->get();

        $products = Product::visibleToCustomers()
            ->with(['category', 'brand', 'images'])
            ->latest()
            ->limit(4)
            ->get();

        $heroPromotions = Promotion::activeNow()
            ->whereNotNull('banner_image')
            ->latest()
            ->limit(3)
            ->get();

        $cartItemsCount = $request->user()?->cart?->items()->sum('quantity') ?? 0;

        return view('pelanggan.dashboard', compact(
            'categories',
            'featuredProducts',
            'latestProducts',
            'products',
            'heroPromotions',
            'cartItemsCount',
        ));
    }
}
