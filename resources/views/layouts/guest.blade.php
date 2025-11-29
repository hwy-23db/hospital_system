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

    <!-- Scripts -->
    @php
        $viteManifestPath = public_path('build/manifest.json');
        $hasViteManifest = file_exists($viteManifestPath);
    @endphp
    @if ($hasViteManifest)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback styles if Vite is not built -->
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                background: #f3f4f6;
                color: #111827;
            }

            .min-h-screen {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 1.5rem;
            }

            .w-full {
                width: 100%;
            }

            .sm\:max-w-md {
                max-width: 28rem;
            }

            .mt-6 {
                margin-top: 1.5rem;
            }

            .px-6 {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }

            .py-4 {
                padding-top: 1rem;
                padding-bottom: 1rem;
            }

            .bg-white {
                background-color: #ffffff;
            }

            .shadow-md {
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            .rounded-lg {
                border-radius: 0.5rem;
            }

            label {
                display: block;
                font-weight: 500;
                margin-top: 1rem;
                color: #374151;
            }

            input {
                width: 100%;
                padding: 0.5rem 0.75rem;
                border: 1px solid #d1d5db;
                border-radius: 0.375rem;
                margin-top: 0.5rem;
                font-size: 0.875rem;
            }

            input:focus {
                outline: none;
                border-color: #3b82f6;
                ring: 2px;
                ring-color: #3b82f6;
            }

            button {
                background: #3b82f6;
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                border: none;
                cursor: pointer;
                margin-top: 1rem;
                font-weight: 500;
            }

            button:hover {
                background: #2563eb;
            }

            .text-red-600 {
                color: #dc2626;
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }
        </style>
    @endif
</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div>
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
