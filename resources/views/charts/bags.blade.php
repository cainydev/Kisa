<div class="grid items-stretch grid-cols-3 gap-8 p-8 bg-white rounded-lg shadow-sm">
    <span class="col-span-full">
        <p class="text-xl font-semibold">Gebindestatistiken</p>
    </span>
    <div>
        {!! $bio->render() !!}
    </div>
    <div>
        {!! $bestbefore->render() !!}
    </div>

    <div class="flex flex-col p-4 space-y-3 overflow-y-scroll border rounded-xl">
        <p class="font-semibold">Läuft bald ab</p>
        @foreach($soonSpoil as $bag)
        <div class="flex items-center justify-between px-2 py-1 bg-gray-200 rounded">
            <p>{{ $bag->herb->name }} #{{ $bag->charge }}<br /><span class="text-sm">{{ $bag->bestbefore->diffForHumans() }}</span></p>
            <a href="{{ route('platform.bags.edit', ['bag' => $bag]) }}">Details</a>
        </div>
        @endforeach
    </div>
</div>
<script>
    window.document.dispatchEvent(new Event("DOMContentLoaded", {
        bubbles: true,
        cancelable: true
    }));
</script>
