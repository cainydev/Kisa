@props(['even' => false, 'right' => false])

@php
$bg = ($even ? 'bg-gray-100' : 'bg-white');
@endphp

<td class='px-2 py-1 overflow-hidden text-left {{ $bg }} truncate first:border-l-2 first:group-hover:border-kgreen'>
    <span @class(['flex
          truncate
          gap-x-4', 'justify-end'=> $right])>
        {{ $slot }}
    </span>
</td>
