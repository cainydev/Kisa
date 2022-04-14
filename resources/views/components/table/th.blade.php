@props(['col' => 'id', 'options' => [
'primary' => false,
'foreign' => false,
'alias' => 'Aktionen',
'description' => 'Alle verfügbaren'
]])

<th {{
    $attributes->merge(['class' => 'relative flex items-center justify-between gap-2 px-2 py-1 overflow-hidden
    text-white
    bg-gray-700 group']) }}>

    {{-- Infos --}}
    <div class="flex items-center justify-between gap-2">
        {{-- Icon --}}
        @if($options['primary'] || $options['foreign'])
        <x-icons.icon-key-solid class="w-4 h-4 shrink-0 fill-{{ $options['primary'] ? 'orange' : 'blue' }}-400" />
        @endif

        {{-- Text Content --}}
        <div class="flex flex-col items-start justify-between h-full">
            <span class="text-sm uppercase">
                {{ $options['alias'] }}
            </span>
            <span class="text-xs lowercase truncate text-kgreen">
                {{ $options['description'] }}
            </span>
        </div>
    </div>

    {{-- Sort --}}
    @if(isset($options['withSort']) && $options['withSort'])
    @livewire('sort-button', ['field' => $col], key($col))
    @endif
</th>
