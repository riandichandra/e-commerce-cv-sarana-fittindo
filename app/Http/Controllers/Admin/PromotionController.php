<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;

class PromotionController extends Controller
{
    public function index()
    {
        $pagePath = explode('/', 'ADMIN/PROMOTIONS');
        $pageName = 'Promosi';
        $promotions = Promotion::with('createdBy')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.promotions.index', compact('pagePath', 'pageName', 'promotions'));
    }
}
