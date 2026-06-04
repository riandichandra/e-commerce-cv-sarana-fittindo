<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $pagePath = 'ADMIN/PRODUCTS/CATEGORIES';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Kategori';
        $categories = ProductCategory::withCount('products')->latest()->paginate(10);

        return view('admin.categories.index', compact('pagePath', 'pageName', 'categories'));
    }

    public function create()
    {
        $pagePath = 'ADMIN/PRODUCTS/CATEGORIES/CREATE';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Tambah Kategori';

        return view('admin.categories.create', compact('pagePath', 'pageName'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:product_categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        ProductCategory::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori produk berhasil ditambahkan.');
    }

    public function edit(ProductCategory $category)
    {
        $pagePath = 'ADMIN/PRODUCTS/CATEGORIES/EDIT';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Edit Kategori';

        return view('admin.categories.edit', compact('pagePath', 'pageName', 'category'));
    }

    public function update(Request $request, ProductCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('product_categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori produk berhasil diperbarui.');
    }

}
