<div class="mb-16">

    <legend class="text-black">Ausschuss</legend>

    <div class="p-4 bg-white rounded shadow-sm">
        <p>Hier kannst du die Menge (in g) an Ausschuss einstellen die bei diesem Sack vorliegt.</p>

        <div class="flex items-center mt-3 space-x-4">
            <input type="number" wire:model="bag.trashed" class="form-control" />
        </div>
        <div class="flex items-center mt-3 space-x-4">
            <button @if($bag->getCurrentWithTrashed() == 0) disabled @endif wire:click="all" class="form-control">Alles</button>
            <button @if($bag->trashed == 0) disabled @endif wire:click="$set('bag.trashed',  0)" class="form-control">Nichts</button>
        </div>

        <span class="flex justify-center w-full mx-auto mt-5 badge rounded-pill bg-light text-dark">Aktueller Füllstand</span>
        <div class="flex items-center mt-2 space-x-4">
            <p class="">{{ $bag->getCurrentWithTrashed() }}g/{{ $bag->size }}g</p>
            <div class="w-full progress">
                <div class="progress-bar bg-success"
                     role="progressbar"
                     style="width: {{ $bag->getCurrentPercentage() }}%"
                     aria-valuenow="25"
                     aria-valuemin="0"
                     aria-valuemax="100"></div>
            </div>
        </div>

    </div>

</div>
