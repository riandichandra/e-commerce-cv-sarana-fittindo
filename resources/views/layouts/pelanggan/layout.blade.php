<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        iconify-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1em;
            height: 1em;
            line-height: 1;
        }

        iconify-icon[class*="fs-1"] {
            font-size: 3.5rem;
        }

        iconify-icon[class*="fs-2"] {
            font-size: 3rem;
        }

        iconify-icon[class*="fs-3"] {
            font-size: 2.25rem;
        }

        iconify-icon[class*="fs-4"] {
            font-size: 1.5rem;
        }

        iconify-icon[class*="fs-5"] {
            font-size: 1.25rem;
        }

        iconify-icon[class*="fs-6"] {
            font-size: 1rem;
        }

        .btn-sm iconify-icon {
            font-size: 1rem;
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-[#0c2037]">
    <div class="min-h-screen bg-[#fff7f8]">
        @include('layouts.pelanggan.navigation')

        @if (session('success') || session('error'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show"
                x-transition
                class="fixed right-6 top-16 z-[60] max-w-sm border-l-4 bg-white px-4 py-3 shadow-xl {{ session('success') ? 'border-green-500 text-green-700' : 'border-red-500 text-red-700' }}">
                <p class="text-sm font-semibold">{{ session('success') ?? session('error') }}</p>
            </div>
        @endif

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>
