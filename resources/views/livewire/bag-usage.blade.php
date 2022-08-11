<div class="p-8 mt-3 bg-white rounded-lg shadow-sm">
    <p class="text-lg font-semibold">Füllstand über Zeit</p>

    <div class="flex flex-col">
        <div class="flex w-full p-2 my-3 space-x-8 border rounded-md">
            <span>
                <p>Startdatum</p>
                <input type="date" wire:model="startDate" />
            </span>
            <span>
                <p>Enddatum</p>
                <input type="date" wire:model="endDate" />
            </span>
        </div>
        <div class="w-full">
            {!! $chart->render() !!}
        </div>
    </div>
</div>
