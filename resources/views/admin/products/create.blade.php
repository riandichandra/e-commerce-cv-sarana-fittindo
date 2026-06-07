<x-admin-layout>
    <div class="flex flex-col gap-2">
        <div class="text-xs flex items-center gap-1">
            <p class="tracking-wider">ADMIN</p>
            <p>></p>
            <p class="tracking-wider">PRODUK</p>
            <p>></p>
            <p class="font-bold text-primary tracking-wider">TAMBAH</p>
        </div>

        <div class="w-full flex items-center justify-between mb-7">
            <h1 class="text-4xl font-bold text-texthighlight">{{ $pageName }}</h1>
            <x-button bgColor="primary" textColor="white" icon="mdi:arrow-left" size="auto"
                href="{{ route('admin.products.index') }}">
                KEMBALI
            </x-button>
        </div>
    </div>

    <div class="bg-[#FFF1F3] p-5 w-full">
        <h2 class="font-semibold tracking-wider text-texthighlight">ADD NEW PRODUCT</h2>
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data"
            class="mt-4 w-full flex flex-col gap-4">
            @csrf

            @include('admin.products.partials.form', ['product' => null])

            <button type="submit" class="bg-primary text-white py-2 px-4 hover:bg-primary-dark transition w-fit">
                TAMBAH PRODUK
            </button>
        </form>
    </div>
</x-admin-layout>
