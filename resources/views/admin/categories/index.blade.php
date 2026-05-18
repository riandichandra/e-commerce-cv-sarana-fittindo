<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">{{ implode(' > ', $pagePath) }}</p>
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
            <tr class="text-left text-xs text-gray-500 font-medium border-b border-gray-300">
                <td class="py-3 px-3 pb-4">NAME</td>
                <td class="py-3 px-3 pb-4">SLUG</td>
                <td class="py-3 px-3 pb-4">DESCRIPTION</td>
                <td class="py-3 px-3 pb-4">ACTIONS</td>
            </tr>
            @if ($categories->isNotEmpty())
            @foreach ($categories as $category)
            <tr class="border-b border-gray-100 text-sm text-texthighlight">
                <td class="py-3 px-3 font-bold text-primary">{{ $category->name }}</td>
                <td class="py-3 px-3">{{ $category->slug }}</td>
                <td class="py-3 px-3">{{ $category->description }}</td>
                <td class="py-3 px-3 flex items-center gap-2">
                    <x-button bgColor="primary" textSize="xs" iconSize="8" textColor="white" icon="mdi:pencil" size="auto"
                        href="{{ route('admin.categories.edit', $category->id) }}">
                        EDIT
                    </x-button>
                    <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST"
                        class="inline-block">
                        @csrf
                        @method('DELETE')
                        <x-button bgColor="red-600" textColor="white" icon="mdi:delete" size="auto"
                            type="submit">
                            DELETE
                        </x-button>
                    </form>
                </td>
            </tr>
            @endforeach
            @else
            <tr>
                <td colspan="4" class="py-3 px-3 text-center text-sm text-gray-500">
                    <div class="flex items-center justify-center gap-2">
                        <iconify-icon icon="mdi:cross-circle" class="nav-small-cap-icon fs-5"></iconify-icon>
                        No categories found.
                    </div>
                </td>
            </tr>
            @endif
        </table>
    </div>
</x-admin-layout>
