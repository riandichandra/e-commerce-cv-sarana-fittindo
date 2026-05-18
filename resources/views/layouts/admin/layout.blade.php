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

<body class="font-sans antialiased text-text">
    <div class="min-h-screen bg-background">
        @if (session('success') || session('error'))
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-2"
                class="fixed right-6 top-6 z-50 w-full max-w-sm bg-white shadow-lg border-l-4 {{ session('success') ? 'border-green-500' : 'border-red-500' }}">
                <div class="flex items-start gap-3 p-4">
                    <div
                        class="mt-0.5 flex h-8 w-8 items-center justify-center {{ session('success') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        <iconify-icon icon="{{ session('success') ? 'mdi:check' : 'mdi:alert-circle' }}" class="fs-5"></iconify-icon>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-texthighlight">
                            {{ session('success') ? 'Berhasil' : 'Gagal' }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ session('success') ?? session('error') }}
                        </p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-700" x-on:click="show = false">
                        <iconify-icon icon="mdi:close" class="fs-5"></iconify-icon>
                    </button>
                </div>
            </div>
        @endif

        <div class="flex justify-between h-screen">
            <div class="w-1/5 h-full">
                @include('layouts.admin.navigation')
            </div>
            <!-- Page Content -->
            <main class="w-4/5 p-9">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
