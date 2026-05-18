<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">{{ implode(' > ', $pagePath) }}</p>
        </div>
        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
        </div>
    </div>
    <div class="bg-[#EFF4FF] p-5 w-full">
        <h2 class="font-semibold tracking-wider text-texthighlight">EDIT CATEGORY</h2>
        <form action="{{ route('admin.categories.update', $category->id) }}" method="POST" class="mt-3 w-full flex flex-col gap-4">
            @csrf
            @method('PUT')
            <div class="flex flex-row gap-4 w-full">
                <div class="flex flex-col gap-1 w-1/2">
                    <label for="name" class="text-sm font-medium text-gray-500">Category Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}"
                        class="border border-gray-300 p-2 text-texthightlight ps-3 focus:ring-primary focus:border-primary transition w-full">
                </div>
                <div class="flex flex-col gap-1 w-1/2">
                    <label for="description" class="text-sm font-medium text-gray-500">Category Description</label>
                    <textarea name="description" id="description"
                        class="border border-gray-300 p-2 text-texthightlight ps-3 focus:ring-primary focus:border-primary transition w-full">{{ old('description', $category->description) }}</textarea>
                </div>
            </div>
            <div class="flex items-center gap-2 w-1/2">
                <button type="submit" class="flex items-center justify-center gap-2 font-medium bg-primary text-white text-sm py-2 px-4 hover:bg-primary-dark transition">
                    <iconify-icon icon="mdi:content-save" class="fs-6"></iconify-icon>
                    SAVE CATEGORY
                </button>
                <a href="{{ route('admin.categories.index') }}">
                    <button type="button" class="flex items-center justify-center gap-2 font-medium bg-gray-500 text-white text-sm py-2 px-4 hover:bg-primary-dark transition">
                        CANCEL
                    </button>
                </a>
            </div>
    </div>
    </form>
    </div>
</x-admin-layout>
