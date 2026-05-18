@php
    $specifications = old('specifications');
    if ($specifications === null && $product?->specifications) {
        $specifications = implode(PHP_EOL, $product->specifications);
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex flex-col gap-1">
        <label for="name" class="text-sm font-medium text-gray-700">Product Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $product?->name) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('name')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="category_id" class="text-sm font-medium text-gray-700">Category</label>
        <select name="category_id" id="category_id"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
            <option value="">Choose category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="brand_id" class="text-sm font-medium text-gray-700">Brand</label>
        <select name="brand_id" id="brand_id"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
            <option value="">No brand</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product?->brand_id) === (string) $brand->id)>
                    {{ $brand->name }}
                </option>
            @endforeach
        </select>
        @error('brand_id')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="price" class="text-sm font-medium text-gray-700">Price</label>
        <input type="number" name="price" id="price" min="0" step="0.01" value="{{ old('price', $product?->price) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('price')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="stock" class="text-sm font-medium text-gray-700">Stock</label>
        <select name="stock" id="stock"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
            <option value="1" @selected((string) old('stock', $product?->stock ?? 1) === '1')>Tersedia</option>
            <option value="0" @selected((string) old('stock', $product?->stock ?? 1) === '0')>Tidak Tersedia</option>
        </select>
        @error('stock')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="weight" class="text-sm font-medium text-gray-700">Weight</label>
        <input type="number" name="weight" id="weight" min="0" step="0.01" value="{{ old('weight', $product?->weight) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('weight')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="thickness" class="text-sm font-medium text-gray-700">Thickness</label>
        <input type="text" name="thickness" id="thickness" value="{{ old('thickness', $product?->thickness) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('thickness')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="dimensions" class="text-sm font-medium text-gray-700">Dimensions</label>
        <input type="text" name="dimensions" id="dimensions" value="{{ old('dimensions', $product?->dimensions) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('dimensions')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="flex flex-col gap-1">
    <label for="description" class="text-sm font-medium text-gray-700">Description</label>
    <textarea name="description" id="description" rows="4"
        class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">{{ old('description', $product?->description) }}</textarea>
    @error('description')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="flex flex-col gap-1">
    <label for="specifications" class="text-sm font-medium text-gray-700">Specifications</label>
    <textarea name="specifications" id="specifications" rows="4"
        class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">{{ $specifications }}</textarea>
    @error('specifications')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex flex-col gap-1">
        <label for="images" class="text-sm font-medium text-gray-700">Product Images</label>
        <input type="file" name="images[]" id="images" multiple
            class="border border-gray-300 bg-white p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('images')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('images.*')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-col gap-1">
        <label for="primary_image" class="text-sm font-medium text-gray-700">Primary Image Index</label>
        <input type="number" name="primary_image" id="primary_image" min="0" value="{{ old('primary_image', 0) }}"
            class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
        @error('primary_image')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

@if ($product?->images?->count())
    <div>
        <p class="text-sm font-medium text-gray-700">Current Images</p>
        <div class="mt-2 grid grid-cols-4 gap-3">
            @foreach ($product->images as $image)
                <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $product->name }}"
                    class="aspect-square w-full object-cover bg-white">
            @endforeach
        </div>
    </div>
@endif

<div class="flex flex-wrap items-center gap-6">
    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="is_featured" value="1" class="text-primary" @checked(old('is_featured', $product?->is_featured ?? false))>
        Featured
    </label>

    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="is_active" value="1" class="text-primary" @checked(old('is_active', $product?->is_active ?? true))>
        Active
    </label>
</div>
