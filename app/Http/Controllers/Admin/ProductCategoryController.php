<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $pagePath = 'ADMIN/PRODUCTS/CATEGORIES';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Categories';

        $categories = ProductCategory::latest()->paginate(10);

        return view('admin.categories.index', compact('pagePath', 'pageName', 'categories'));
    }

    public function create()
    {
        $pagePath = 'ADMIN/PRODUCTS/CATEGORIES/CREATE';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Create Category';

        return view('admin.categories.create', compact('pagePath', 'pageName'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Create Category
        $product = ProductCategory::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(ProductCategory $category)
    {
        $pagePath = 'ADMIN/PRODUCTS/CATEGORIES/EDIT';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Edit Category';

        return view('admin.categories.edit', compact('category', 'pagePath', 'pageName'));
    }


    public function update(Request $request, ProductCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(ProductCategory $category)
    {
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
