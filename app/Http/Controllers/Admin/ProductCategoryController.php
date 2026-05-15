<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $pagePath = 'ADMIN/PRODUCTS/CATEGORIES';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Categories';

        return view('admin.categories.index', compact('pagePath', 'pageName'));
    }

    public function create()
    {
        $pagePath = 'ADMIN/PRODUCTS/CATEGORIES/CREATE';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Create Category';

        return view('admin.categories.create', compact('pagePath', 'pageName'));
    }
}
