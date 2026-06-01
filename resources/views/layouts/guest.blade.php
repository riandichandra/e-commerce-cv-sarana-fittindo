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

<body class="font-sans text-[#0b1735] antialiased">
    <main class="min-h-screen overflow-hidden bg-[#fff2f5]">
        <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[1.52fr_1fr]">
            <section class="relative hidden min-h-screen overflow-hidden bg-[#061b3c] lg:block">
                <img src="{{ asset('images/auth/sarana-auth-hero.png') }}" alt="CV Sarana Fittindo"
                    class="absolute inset-0 h-full w-full object-cover object-left">
                <div class="absolute inset-0 bg-gradient-to-r from-[#031231]/10 via-transparent to-[#031231]/15"></div>
            </section>

            <section class="relative flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
                <div class="absolute inset-0 bg-[#fff2f5]"></div>
                <div class="absolute inset-0 opacity-70"
                    style="background-image: radial-gradient(circle at 20% 10%, rgba(200,16,46,.14), transparent 28%), radial-gradient(circle at 80% 80%, rgba(255,255,255,.9), transparent 32%), repeating-linear-gradient(42deg, rgba(200,16,46,.08) 0 1px, transparent 1px 18px);">
                </div>

                <div class="relative w-full max-w-[560px]">
                    <div class="mb-6 flex items-center justify-between lg:hidden">
                        <a href="{{ route('dashboard') }}"
                            class="text-sm font-black uppercase tracking-[.16em] text-[#c8102e]">
                            CV. Sarana Fittindo
                        </a>
                        <a href="{{ route('dashboard') }}"
                            class="text-xs font-bold uppercase tracking-[.14em] text-[#5d7190]">
                            Home
                        </a>
                    </div>

                    <div class="rounded-[28px] border border-white/80 bg-white/95 p-7 shadow-[0_30px_90px_rgba(10,28,64,.16)] backdrop-blur sm:p-10 lg:p-12">
                        {{ $slot }}
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>

</html>
