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
            <div class="progress-bar progress-bar-striped {{ $batch->cancelled() ? 'bg-danger' : 'bg-success progress-bar-animated' }}"
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

    @if($batch && $batch->finished())
    <table class="w-full bg-white border border-collapse border-black table-auto">
        <thead>
            <tr>
                <th class="p-2">Kraut</th>
                <th class="p-2">Verbrauch/Monat</th>
                <th class="p-2">Verbrauch/Jahr</th>
                <th class="p-2">Gramm übrig</th>
                <th class="p-2">Tage übrig</th>
            </tr>
        </thead>
        <tbody>
            @foreach(\App\Models\Herb::with('bags')->get()->filter(function(\App\Models\Herb $herb){
            return $herb->getRedisAveragePerDay() > 0;
            })->sortBy(function($herb) {
            return $herb->getRedisDaysRemaining();
            }) as $herb)
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
    @endif
</div>