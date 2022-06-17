<div class="bg-white rounded-md shadow mt-3 p-4">
    <ul class="nav nav-tabs"
        id="myTab"
        role="tablist">
        @forelse($herb->bags as $bag)
        <li class="nav-item"
            role="presentation">
            <button class="nav-link @if($loop->first) active @endif"
                    id="{{ $bag->charge }}-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#bag{{ $bag->charge }}"
                    type="button"
                    role="tab"
                    aria-controls="{{ $bag->charge }}"
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
             id="bag{{ $bag->charge }}"
             role="tabpanel"
             aria-labelledby="{{ $bag->charge }}-tab">
            <div class="border-b space-y-1 border-x rounded-b-md mb-3 p-3">
                <button class="btn btn-primary"
                        wire:click="printPDF({{ $bag->id }})">PDF Drucken</button>
            </div>
            <x-herb-statistic :bag="$bag" />
        </div>
        @endforeach
    </div>
</div>
