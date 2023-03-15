@php
use \Illuminate\Support\Facades\Redis;
use \App\Models\Herb;

$batch = Bus::findBatch($batch_uuid);

@endphp

<div class="space-y-3"
     @unless($batch->finished()) wire:poll.300ms @endunless>
    @unless($batch->finished())



    <p>Analysiere Rohstoffe...</p>
    <span class="flex items-center space-x-2">
        <label for="batch">{{ $batch->processedJobs(); }} / {{ $batch->totalJobs; }}</label>
        <progress id="batch"
                  class="w-full rounded"
                  max="100"
                  value="{{ $batch->progress(); }}"></progress>
    </span>
    @endunless

    @if($batch->finished())
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
            @foreach(Herb::with('bags')->get()->filter(function(Herb $herb){
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