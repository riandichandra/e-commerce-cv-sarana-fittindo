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
            <x-button bgColor="primary" textColor="white" icon="mdi:plus" size="auto"
                href="{{ route('admin.categories.create') }}">
                ADD CATEGORY
            </x-button>
        </div>

    </div>

    <div class="bg-[#EFF4FF] p-5 w-full">
        <h2 class="font-semibold tracking-wider text-texthighlight">CATEGORY LISTS</h2>
        <table class="mt-3 w-full">
            <tr class="text-left text-sm text-gray-600 font-medium border-b border-gray-300">
                <td class="py-3 px-3">#</td>
                <td class="py-3 px-3">ID</td>
                <td class="py-3 px-3">Name</td>
                <td class="py-3 px-3">Price</td>
                <td class="py-3 px-3">Price</td>
                <td class="py-3 px-3">Price</td>
                <td class="py-3 px-3">Stock</td>
                <td class="py-3 px-3">Actions</td>
            </tr>
        </table>
    </div>
</x-admin-layout>
