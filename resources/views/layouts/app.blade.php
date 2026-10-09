<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->user()?->theme === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#00adb7">
        <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">

        <title>{{ $title ?? config('app.name', 'AV Asset Manager') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-brand-silver text-brand-black dark:bg-black dark:text-white">
        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white dark:bg-brand-slate border-b border-brand-silver dark:border-brand-charcoal">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @if (session('status'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="rounded border border-brand bg-white dark:bg-brand-slate px-4 py-3 text-sm text-brand-teal dark:text-brand-teal-bright">
                        {{ session('status') }}
                    </div>
                </div>
            @endif
            @if (session('warning'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="rounded border border-brand-gold bg-white dark:bg-brand-slate px-4 py-3 text-sm text-brand-charcoal dark:text-brand-gold">
                        {{ session('warning') }}
                    </div>
                </div>
            @endif

            <main class="py-6">
                {{ $slot }}
            </main>
        </div>

        @if ($gitVersion = \App\Support\GitVersion::short())
            <div class="fixed bottom-2 left-2 z-10 pointer-events-none select-none text-xs font-mono text-brand-charcoal dark:text-brand-grey-blue" title="Deployed commit">
                {{ $gitVersion }}
            </div>
        @endif
    </body>
</html>
