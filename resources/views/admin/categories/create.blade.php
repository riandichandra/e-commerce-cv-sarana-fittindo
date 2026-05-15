<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            @for ($i = 0; $i < count($pagePath) - 1; $i++)
                <p class="tracking-wider">{{ $pagePath[$i] }}</p>
                <p>></p>
                <p class="font-bold text-primary tracking-wider">{{ $pagePath[$i++] }}</p>
                @if ($i == count($pagePath) - 1)
                    @break
                @endif
                <p>></p>
            @endfor
        </div>
        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
        </div>
    </div>
    <div class="bg-[#EFF4FF] p-5 w-full">
        <h2 class="font-semibold tracking-wider text-texthighlight">ADD NEW CATEGORY</h2>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="mt-3 w-full flex flex-col gap-4">
            @csrf
            <div class="flex flex-row gap-4 w-full">
                <div class="flex flex-col gap-1 w-1/2">
                    <label for="name" class="text-sm font-medium text-gray-700">Category Name</label>
                    <input type="text" name="name" id="name"
                        class="border border-gray-300 rounded-md p-2 focus:ring-primary focus:border-primary transition w-full">
                </div>
                <div class="flex flex-col gap-1 w-1/2">
                    <label for="description" class="text-sm font-medium text-gray-700">Category Description</label>
                    <textarea name="description" id="description"
                        class="border border-gray-300 rounded-md p-2 focus:ring-primary focus:border-primary transition w-full"></textarea>
                </div>
            </div>
    </div>
    <button type="submit" class="bg-primary text-white py-2 px-4 rounded-md hover:bg-primary-dark transition w-1/4">
        CREATE CATEGORY
    </button>
    </form>
    </div>
</x-admin-layout>
