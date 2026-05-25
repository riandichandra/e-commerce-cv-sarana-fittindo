<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    public function index()
    {
        $pagePath = explode('/', 'MARKETING/PROMOTIONS');
        $pageName = 'Promotions';
        $promotions = Promotion::with('createdBy')
            ->latest()
            ->paginate(10);

        return view('marketing.promotions.index', compact('pagePath', 'pageName', 'promotions'));
    }

    public function create()
    {
        $pagePath = explode('/', 'MARKETING/PROMOTIONS/CREATE');
        $pageName = 'Create Promotion';
        $promotion = new Promotion([
            'type' => 'percent',
            'is_active' => true,
            'start_date' => now(),
            'end_date' => now()->addDays(7),
        ]);

        return view('marketing.promotions.create', compact('pagePath', 'pageName', 'promotion'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by'] = Auth::id();

        if ($request->hasFile('banner_image')) {
            $validated['banner_image'] = $request->file('banner_image')->store('promotions', 'public');
        }

        Promotion::create($validated);

        return redirect()
            ->route('marketing.promotions.index')
            ->with('success', 'Promotion berhasil ditambahkan.');
    }

    public function edit(Promotion $promotion)
    {
        $pagePath = explode('/', 'MARKETING/PROMOTIONS/EDIT');
        $pageName = 'Edit Promotion';

        return view('marketing.promotions.edit', compact('pagePath', 'pageName', 'promotion'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $validated = $this->validatedData($request, $promotion);
        $validated['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('banner_image')) {
            if ($promotion->banner_image) {
                Storage::disk('public')->delete($promotion->banner_image);
            }

            $validated['banner_image'] = $request->file('banner_image')->store('promotions', 'public');
        }

        $promotion->update($validated);

        return redirect()
            ->route('marketing.promotions.index')
            ->with('success', 'Promotion berhasil diperbarui.');
    }

    private function validatedData(Request $request, ?Promotion $promotion = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('promotions', 'code')->ignore($promotion?->id),
            ],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['nominal', 'percent'])],
            'value' => ['required', 'numeric', 'min:0'],
            'min_purchase' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'banner_image' => ['nullable', 'image', 'max:2048'],
            'banner_url' => ['nullable', 'url', 'max:255'],
        ]);
    }
}
