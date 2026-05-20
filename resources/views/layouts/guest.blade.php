<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-[#10233d] antialiased">
    <main class="min-h-screen bg-[#fff1f3]">
        <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[1.05fr_.95fr]">
            <section class="relative hidden overflow-hidden bg-[#071d33] lg:block">
                <div class="absolute inset-0 bg-cover bg-center opacity-70"
                    style="background-image: linear-gradient(90deg, rgba(4,17,45,.92), rgba(4,17,45,.38)), url('{{ asset('storage/products/dhpkAn0vG4mLZgmK34IkMPhkLMmIDLk5zAIMcp4M.jpg') }}');">
                </div>
                <div class="absolute inset-0"
                    style="background-image: repeating-linear-gradient(0deg, rgba(255,255,255,.08) 0 1px, transparent 1px 18px);">
                </div>

                <div class="relative flex h-full flex-col justify-between p-14 text-white">
                    <a href="{{ route('dashboard') }}" class="text-sm font-black uppercase tracking-[.18em]">
                        Sarana Fittindo
                    </a>

                    <div class="max-w-[620px] pb-10">
                        <p class="mb-5 text-xs font-black uppercase tracking-[.35em] text-[#c8d8ee]">Customer Portal</p>
                        <h1 class="text-[64px] font-black uppercase leading-[.95] tracking-[-.04em]">
                            Architectural<br>Precision.
                        </h1>
                        <p class="mt-7 max-w-[500px] text-base leading-8 text-[#c6d6ef]">
                            Akses koleksi material interior premium, kelola profil akun, dan temukan produk terbaik
                            untuk kebutuhan proyek Anda.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-xs font-bold uppercase tracking-[.14em] text-[#d6e6fb]">
                        <div class="border-t border-white/20 pt-4">HPL Panels</div>
                        <div class="border-t border-white/20 pt-4">Plywood</div>
                        <div class="border-t border-white/20 pt-4">Adhesives</div>
                    </div>
                </div>
            </section>

            <section class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
                <div class="w-full max-w-[480px]">
                    <div class="mb-8 flex items-center justify-between lg:hidden">
                        <a href="{{ route('dashboard') }}"
                            class="text-sm font-black uppercase tracking-[.16em] text-[#c8102e]">
                            Sarana Fittindo
                        </a>
                        <a href="{{ route('dashboard') }}"
                            class="text-xs font-bold uppercase tracking-[.14em] text-[#5d7190]">
                            Home
                        </a>
                    </div>

                    <div class="border border-[#f2c8d0] bg-white p-7 shadow-[0_24px_70px_rgba(9,33,68,.12)] sm:p-10">
                        {{ $slot }}
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>

</html>
