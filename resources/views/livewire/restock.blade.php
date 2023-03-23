<div class="space-y-3"
     @if($batch
     &&
     !$batch->finished()) wire:poll.300ms @endif>

    <div class="flex flex-wrap items-stretch justify-between gap-8">
        <span class="flex flex-col justify-start gap-1">
            <p>Max % Ausschuss pro Sack</p>
            <input type="number"
                   wire:model="trashGate"
                   max="100"
                   min="0">
            <p class="text-sm text-gray-700">Falls mehr % Ausschuss entdeckt werden, wird der Ausschuss nicht
                mitverrechnet</p>
            @error('trashGate')
            <p class="text-red-500">{{ $message }}</p>
            @enderror
        </span>

        <span class="flex items-start gap-3">
            <span class="flex flex-col gap-1">
                <p>Startdatum</p>
                <input wire:model="startDate"
                       type="date">
                @error('startDate')
                <p class="text-red-500">{{ $message }}</p>
                @enderror
            </span>
            <span class="flex flex-col gap-1">
                <p>Enddatum</p>
                <input wire:model="endDate"
                       type="date">
                @error('endDate')
                <p class="text-red-500">{{ $message }}</p>
                @enderror
            </span>
        </span>
        <span class="flex items-center">
            <button class="btn btn-success"
                    wire:click="generate">Analysieren</button>
        </span>

    </div>


    @if($batch && !$batch->finished())
    <p>Analysiere Rohstoffe...</p>
    <span class="flex items-center space-x-2">
        <label for="batch">{{ $batch->processedJobs(); }} / {{ $batch->totalJobs; }}</label>
        <div class="w-full progress bg-slate-200">
            <div class="progress-bar progress-bar-striped bg-success progress-bar-animated"
                 role="progressbar"
                 style="width: {{ $batch->progress() }}%"
                 aria-valuenow="{{ $batch->progress() }}"
                 aria-valuemin="0"
                 aria-valuemax="100"></div>
        </div>
        <button class="btn btn-danger"
                wire:click="abort">
            Abbrechen
        </button>
    </span>
    @endif

    @if(!$batch || $batch && $batch->finished())
    <div>
        <div class="flex items-center justify-end w-full py-2 space-x-3">
            <p>Sortierung:</p>
            <select class="form-select"
                    wire:model="sort">
                <option value="name">Name</option>
                <option value="use">Verbrauch</option>
                <option value="grammremaining">Gramm übrig</option>
                <option value="daysremaining">Tage übrig</option>
            </select>
            <select class="form-select"
                    wire:model="sortDir">
                <option value="asc">Aufsteigend</option>
                <option value="desc">Absteigend</option>
            </select>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Kraut</th>
                    <th scope="col">Verbrauch/Monat</th>
                    <th scope="col">Verbrauch/Jahr</th>
                    <th scope="col">Gramm übrig</th>
                    <th scope="col">Tage übrig</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $herb)
                <tr class="odd:bg-gray-200">
                    <td class="p-2">{{ $herb->name }}</td>
                    <td class="p-2">{{ round($herb->getRedisAveragePerMonth(), 2) }}g</td>
                    <td class="p-2">{{ round($herb->getRedisAveragePerYear(), 2) }}g</td>
                    <td class="p-2">
                        {{ round($herb->getRedisGrammRemaining(), 2) }}
                        /
                        {{ round($herb->getRedisBought(), 2) }}g</td>
                    <td class="p-2">{{ round($herb->getRedisDaysRemaining()) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>