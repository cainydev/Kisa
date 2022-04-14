<div x-data
     x-init="$el.parentElement.classList.add('relative', 'group'); $el.style.bottom = (($el.offsetHeight + 12) * -1) + 'px'"
     class="absolute transition-transform scale-0 -translate-x-1/2 group-hover:scale-100 left-1/2">

    <p id="tooltip"
       {{
       $attributes->merge(['class' => 'z-20 px-2 py-1 text-xs bg-white border-2 rounded-md before:w-2 before:h-2
        before:bg-gray-200 before:absolute before:left-1/2 before:-translate-x-1/2 before:rotate-45 before:-top-1
        before:-z-10 text-bytext whitespace-nowrap'])
        }}>
        {{ $slot }}
    </p>

</div>
