<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBrand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductBrandController extends Controller
{
    public function index()
    {
        $pagePath = 'ADMIN/PRODUCTS/BRANDS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Brands';
        $brands = ProductBrand::withCount('products')->latest()->paginate(10);

        return view('admin.brands.index', compact('pagePath', 'pageName', 'brands'));
    }

    public function create()
    {
        $pagePath = 'ADMIN/PRODUCTS/BRANDS/CREATE';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Create Brand';

        return view('admin.brands.create', compact('pagePath', 'pageName'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:product_brands,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        ProductBrand::create($validated);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Merek produk berhasil ditambahkan.');
    }

    public function edit(ProductBrand $brand)
    {
        $pagePath = 'ADMIN/PRODUCTS/BRANDS/EDIT';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Edit Brand';

        return view('admin.brands.edit', compact('pagePath', 'pageName', 'brand'));
    }

    public function update(Request $request, ProductBrand $brand)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('product_brands', 'name')->ignore($brand->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $brand->update($validated);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Merek produk berhasil diperbarui.');
    }

}
