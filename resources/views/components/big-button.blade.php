<a {{
   $attributes->merge(['class' => 'flex flex-col gap-2 p-4 text-left border-4 shadow-inner hover:border-kgreen']) }}>

    @isset($icon)
    {{ $icon }}
    @endisset

    @isset($title)
    <h1 class="text-xl text-gray-800">{{ $title }}</h1>
    @endisset

    @isset($slot)
    <p>{{ $slot }}</p>
    @endisset

</a>
