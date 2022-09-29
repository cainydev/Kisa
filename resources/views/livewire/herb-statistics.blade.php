<div>
    <div class="px-4 py-2 mt-3 bg-white rounded-md shadow">
        <div class="p-3 my-3 space-y-1 border rounded-md">
            <h2 class="text-lg font-semibold">Statistik generieren für:</h2>
            <hr class="mb-3">
            <div wire:loading.remove>
                @forelse($herb->bags as $availableBag)
                <button class="btn btn-primary" wire:click="generateFor({{ $availableBag->id }})">
                    <span>Charge #{{ $availableBag->charge }}</span>
                </button>
                @empty
                Keine Chargen vorhanden.
                @endforelse
            </div>
            <button class="h-10 btn btn-primary" type="button" disabled wire:loading.flex>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span class="visually-hidden">Wird generiert...</span>
            </button>
        </div>
    </div>
    <div class="p-4 mt-3 bg-white rounded-md shadow">
        @if(isset($bag))
        <div class="flex items-center justify-between p-3 mb-3 space-y-1 border rounded-md">
            <p class="text-lg font-semibold">Statistik generiert für Charge #{{ $bag->charge}}</p>
            <button class="btn btn-primary" wire:click="printPDF({{ $bag->id }})">PDF Drucken</button>
        </div>

        <x-herb-statistic :bag="$bag" />
        @else
        <p>Wähle aus, für welche Charge die Statistik generiert werden soll.</p>
        @endif
    </div>
</div>
