<div>
    <div class="px-4 py-2 mt-3 bg-white rounded-md shadow">
        <div class="p-3 my-3 space-y-1 border rounded-md">
            <h2 class="text-lg font-semibold">Statistik generieren für:</h2>
            <hr class="mb-3">
            <div>
                @forelse($herb->bags as $availableBag)
                <button class="btn btn-primary" wire:click="generateFor({{ $availableBag->id }})">
                    <span wire:loading.remove>Charge #{{ $availableBag->charge }}</span>
                    <x-icons.icon-loading wire:loading.flex class="w-8 h-8 mx-auto fill-white" />
                </button>
                @empty
                Keine Chargen vorhanden.
                @endforelse
            </div>
        </div>
    </div>
    <div class="p-4 mt-3 bg-white rounded-md shadow">
        <div class="tab-pane fade show" id="bag{{ $bag->id }}" role="tabpanel" aria-labelledby="{{ $bag->id }}-tab">
            <div class="flex items-center justify-between p-3 mb-3 space-y-1 border rounded-md">
                <p class="text-lg font-semibold">Statistik generiert für Charge #{{ $bag->charge}}</p>
                <button class="btn btn-primary" wire:click="printPDF({{ $bag->id }})">PDF Drucken</button>
            </div>
            <x-herb-statistic :bag="$bag" />
        </div>
    </div>
</div>
