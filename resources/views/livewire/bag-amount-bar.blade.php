{{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}

<x-filament::section class="[&>div>div]:p-1 mt-6">
    @php
        $total = $this->bag->size;
        $trashed = $this->bag->trashed;
        $used = $total - $this->bag->getCurrent();

        $usedP = ($used / $total) * 100;
        $trashedP = ($trashed / $total) * 100;
    @endphp
    <div class="flex items-stretch h-8 gap-1.5">
        @if($usedP > 0)
            <div class="relative group hover:min-w-max rounded-l-lg px-2 transition-all bg-blue-500 flex items-center justify-center origin-top-left" @style(["flex-basis: $usedP%"])>
                <p class="absolute bottom-full left-0 -translate-y-2 px-2 origin-bottom-left whitespace-nowrap shadow shadow-blue-500 bg-gray-800 py-1 rounded-lg opacity-50 group-hover:opacity-100 border-2 border-blue-500 scale-0 group-hover:scale-100 transition-all">Verbraucht: {{ $used }}g</p>
            </div>
        @endif

        @if($trashedP > 0)
            <div class="relative transition-all @if($usedP == 0) rounded-l-lg @endif @if($usedP + $trashedP >= $total) rounded-r-lg @endif group origin-top-left bg-red-500 flex items-center justify-center" @style(["flex-basis: $trashedP%"])>
                <p class="absolute bottom-full left-0 -translate-y-2 px-2 origin-bottom-left whitespace-nowrap py-1 rounded-lg shadow shadow-red-500 bg-gray-800 border-2 border-red-500 scale-0 opacity-50 group-hover:opacity-100 group-hover:scale-100 transition-all">Ausschuss: {{ $trashed }}g</p>
            </div>
            @endif
            @if($usedP + $trashedP < $total)
            <div class="relative transition-all group hover:min-w-max px-2 rounded-r-lg bg-green-500 flex items-center justify-center grow origin-top-left">
                <p class="absolute bottom-full left-0 -translate-y-2 px-2 origin-bottom-left whitespace-nowrap py-1 rounded-lg border-2 shadow shadow-green-500 bg-gray-800 border-green-500 scale-0 opacity-50 group-hover:opacity-100 group-hover:scale-100 transition-all">Verbleibend: {{ $total - $used - $trashed }}g</p>
            </div>
            @endif
    </div>
</x-filament::section>
