<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Promotion;

class DashboardController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::active()->limit(4)->get();
        $latestProducts = Product::active()
            ->with(['category', 'images'])
            ->latest()
            ->limit(3)
            ->get();
        $products = Product::active()
            ->with(['category', 'images'])
            ->latest()
            ->limit(4)
            ->get();
        $heroPromotions = Promotion::where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->whereNotNull('banner_image')
            ->latest()
            ->limit(5)
            ->get();

        return view('pelanggan.dashboard', compact('categories', 'latestProducts', 'products', 'heroPromotions'));
    }
}
