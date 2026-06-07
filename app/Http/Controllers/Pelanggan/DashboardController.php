<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Promotion;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $categories = ProductCategory::active()
            ->withCount(['products' => fn ($query) => $query->active()])
            ->limit(6)
            ->get();

        $featuredProducts = Product::active()
            ->featured()
            ->with(['category', 'brand', 'images'])
            ->latest()
            ->limit(4)
            ->get();

        $latestProducts = Product::active()
            ->with(['category', 'brand', 'images'])
            ->latest()
            ->limit(8)
            ->get();

        $products = Product::active()
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
