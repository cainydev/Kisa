@props(['error' => false, 'title' => 'Allgemein'])

@php
$color = 'gray-300';
if($error) $color = 'red-500';
@endphp


<section {{
         $attributes->merge(['class'=>'bg-gray-100 transition-colors gap-4 m-4 relative p-6 border-2 rounded-b-md
    border-'.$color]) }}>
    <h2 style="left:-"
        class="{{ 'bg-'.$color }} transition-colors px-2 absolute -top-7 -left-0.5 border-2 border-gray-300 text-base font-semibold text-gray-800 rounded-t-md">
        {{ $title }}
    </h2>
    {{ $slot }}
</section>
