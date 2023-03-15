@php
use \Illuminate\Support\Facades\Redis;
use \App\Models\Herb;

$batch = Bus::findBatch($batch_uuid);
@endphp

<div class="space-y-3"
     @unless($batch->finished()) wire:poll.1000ms @endunless>
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
    <table class="w-full border border-collapse border-black table-auto">
        <thead>
            <tr>
                <th class="p-2">Kraut</th>
                <th class="p-2">Verbrauch/Tag</th>
                <th class="p-2">Verbrauch/Monat</th>
                <th class="p-2">Verbrauch/Jahr</th>
                <th class="p-2">Übrig</th>
            </tr>
        </thead>
        <tbody>
            @foreach(Herb::with('bags')->get()->sortBy(function($herb) {
            return Redis::get('herb:' . $herb->id . ':remaining');
            }) as $herb)
            <tr class="odd:bg-gray-200">
                <td class="p-2">{{ $herb->name }}</td>
                <td class="p-2">{{ Redis::get('herb:' . $herb->id . ':per.day') }}g</td>
                <td class="p-2">{{ Redis::get('herb:' . $herb->id . ':per.month') }}g</td>
                <td class="p-2">{{ Redis::get('herb:' . $herb->id . ':per.year') }}g</td>
                <td class="p-2">
                    {{ Redis::get('herb:' . $herb->id . ':remaining') }}
                    /
                    {{ Redis::get('herb:' . $herb->id . ':bought') }}g</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>