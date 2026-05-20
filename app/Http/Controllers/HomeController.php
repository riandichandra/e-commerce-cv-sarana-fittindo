<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
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
        return view("pelanggan.dashboard");
    }
}
