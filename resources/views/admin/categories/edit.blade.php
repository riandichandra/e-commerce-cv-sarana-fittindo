<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="tracking-wider">PRODUK</p>
            <p>></p>
            <p class="tracking-wider">KATEGORI</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">EDIT</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <x-button bgColor="primary" textColor="white" icon="mdi:arrow-left" size="auto"
                href="{{ route('admin.categories.index') }}">
                KEMBALI
            </x-button>
        </div>
    </div>

    <div class="bg-[#FFF1F3] p-5 w-full">
        <h2 class="font-semibold tracking-wider text-texthighlight">EDIT KATEGORI</h2>
        <form action="{{ route('admin.categories.update', $category) }}" method="POST"
            class="mt-4 w-full flex flex-col gap-4">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-1">
                <label for="name" class="text-sm font-medium text-gray-700">Nama Kategori</label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}"
                    class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">
                @error('name')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-1">
                <label for="description" class="text-sm font-medium text-gray-700">Kategori Deskripsi</label>
                <textarea name="description" id="description" rows="4"
                    class="border border-gray-300 p-2 focus:ring-primary focus:border-primary transition w-full">{{ old('description', $category->description) }}</textarea>
                @error('description')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" class="text-primary"
                    @checked(old('is_active', $category->is_active))>
                Aktif
            </label>

            <button type="submit" class="bg-primary text-white py-2 px-4 hover:bg-primary-dark transition w-fit">
                UPDATE KATEGORI
            </button>
        </form>
    </div>
</x-admin-layout>
