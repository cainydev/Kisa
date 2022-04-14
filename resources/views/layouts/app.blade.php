<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">
    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    {{-- Favicon --}}
    <link rel="apple-touch-icon"
          sizes="180x180"
          href="{{ asset('/images/favicon/apple-touch-icon.png') }}">
    <link rel="icon"
          type="image/png"
          sizes="32x32"
          href="{{ asset('/images/favicon/favicon-32x32.png') }}">
    <link rel="icon"
          type="image/png"
          sizes="16x16"
          href="{{ asset('/images/favicon/favicon-16x16.png') }}">
    <link rel="manifest"
          href="{{ asset('/images/favicon/site.webmanifest') }}">
    <link rel="mask-icon"
          href="{{ asset('/images/favicon/safari-pinned-tab.svg') }}"
          color="#128237">
    <link rel="shortcut icon"
          href="{{ asset('/images/favicon/favicon.ico') }}">
    <meta name="msapplication-TileColor"
          content="#00a300">
    <meta name="msapplication-config"
          content="{{ asset('/images/favicon/browserconfig.xml') }}">
    <meta name="theme-color"
          content="#ffffff">

    <title>{{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Styles -->
    <link rel="stylesheet"
          href="{{ asset('css/app.css') }}">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"
            defer></script>

    @livewireStyles
</head>

<body class="overflow-y-scroll font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        <header class="relative bg-gray-800">
            <div class="z-20 px-4 py-4 mx-auto bg-gray-800 max-w-7xl sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>

        <!-- Page Content -->
        <main class="relative bg-transparent">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>

</html>
