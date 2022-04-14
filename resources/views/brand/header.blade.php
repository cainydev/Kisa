@push('head')
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3"
      crossorigin="anonymous">
@endpush

@auth
<div class="flex items-center justify-start gap-6 -mt-6">
    <span class="bg-white rounded-full">
        <x-logo.min class="w-10 h-10 m-2" />
    </span>


    <span class="text-lg font-semibold text-white">
        Kräuter & Wege
    </span>
</div>
@endauth

@guest
<div class="flex flex-col items-center justify-center gap-6 px-3 py-2">
    <x-logo.min class="w-14 h-14" />

    <span class="text-2xl font-semibold text-gray-800">
        Kräuter & Wege
    </span>
</div>
@endguest
