<div class="p-4 mt-3 bg-white rounded-md shadow">
    <ul class="nav nav-tabs"
        id="myTab"
        role="tablist">
        @forelse($herb->bags as $bag)
        <li class="nav-item"
            role="presentation">
            <button class="nav-link @if($loop->first) active @endif"
                    id="{{ $bag->id }}-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#bag{{ $bag->id }}"
                    type="button"
                    role="tab"
                    aria-controls="{{ $bag->id }}"
                    aria-selected="true">Charge {{ $bag->charge }}</button>
        </li>
        @empty
        Keine Chargen vorhanden.
        @endforelse
    </ul>
    <div class="tab-content"
         id="myTabContent">
        @foreach ($herb->bags as $bag)
        <div class="tab-pane fade show @if($loop->first) active @endif"
             id="bag{{ $bag->id }}"
             role="tabpanel"
             aria-labelledby="{{ $bag->id }}-tab">
            <div class="p-3 mb-3 space-y-1 border-b border-x rounded-b-md">
                <button class="btn btn-primary"
                        wire:click="printPDF({{ $bag->id }})">PDF Drucken</button>
            </div>
            <x-herb-statistic :bag="$bag" />
        </div>
        @endforeach
    </div>
</div>
