<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

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

        iconify-icon[class*="fs-4"] {
            font-size: 1.5rem;
        }

        iconify-icon[class*="fs-5"] {
            font-size: 1.25rem;
        }

        iconify-icon[class*="fs-6"] {
            font-size: 1rem;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-text">
    <div class="min-h-screen bg-background">
        <div class="flex h-screen justify-between">
            <div class="h-full w-1/5">
                @include('layouts.direktur.navigation')
            </div>

            <main class="w-4/5 overflow-y-auto p-9">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
