{{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}

<x-filament::section class="[&>div>div]:p-1 mt-6">
    @php
        $total = $this->bag->size;
        $trashed = $this->bag->trashed;
        $used = 400;//$total - $this->bag->getCurrent();

        $usedP = ($used / $total) * 100;
        $trashedP = ($trashed / $total) * 100;
    @endphp
    <div class="flex items-stretch h-8 gap-1.5">
        @if($usedP + $trashedP < $total)
            <div
                class="flex items-center justify-left px-2 rounded-l-lg @if($used + $trashed === 0) rounded-r-lg @else rounded-r-sm @endif bg-green-500 grow">
                <p class="uppercase text-sm font-semibold whitespace-nowrap text-green-900">
                    rest / {{ $total - $used - $trashed }}g
                </p>
            </div>
        @endif
        @if($usedP > 0)
            <div
                class="flex items-center justify-left px-2 @if($total - $used - $trashed === 0) rounded-l-lg @else rounded-l-sm @endif @if($trashed === 0) rounded-r-lg @else rounded-r-sm @endif transition-all bg-yellow-500" @style(["flex-basis: $usedP%"])>
                <p class="uppercase text-sm font-semibold whitespace-nowrap text-yellow-900">
                    used / {{ $used }}g
                </p>
            </div>
        @endif
        @if($trashedP > 0)
            <div
                class="flex items-center justify-left px-2 @if($trashed === $total) rounded-l-lg @else rounded-l-sm @endif rounded-r-lg bg-red-500" @style(["flex-basis: $trashedP%"])>
                <p class="uppercase text-sm font-semibold whitespace-nowrap text-red-900">
                    trash / {{ $trashed }}g
                </p>
            </div>
        @endif
    </div>
</x-filament::section>
