<?php

namespace App\Http\Controllers\Pelanggan;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;

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

        return view('pelanggan.dashboard', compact('categories', 'latestProducts', 'products'));
    }
}
