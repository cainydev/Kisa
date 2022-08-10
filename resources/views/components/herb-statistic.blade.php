@props(['bag'])
@php
$herb = $bag->herb;
@endphp
{{-- General --}}
<div class="p-3 my-3 space-y-1 border rounded-md">
    <h2 class="text-lg font-semibold">Allgemein</h2>
    <hr class="mb-3">
    <p>Produkt: {{ $herb->name }}</p>
    <p>Charge: {{ $bag->charge }}</p>
    <p>Wareneingang Menge: {{ $bag->size }}g</p>
    <p>Lieferant: {{ $bag->delivery->supplier->company }}</p>
</div>
{{-- Abfüllungen --}}
<div class="p-3 my-3 space-y-1 border rounded-md">
    <h2 class="text-lg font-semibold">Abfüllungen</h2>
    <hr class="mb-3">
    @if($bag->ingredients->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th scope="col" class="font-semibold text-black">Datum</th>
                <th scope="col" class="font-semibold text-black">Produkt</th>
                <th scope="col" class="font-semibold text-black">Einheiten</th>
                <th scope="col" class="font-semibold text-black">Verwendete Menge</th>
                <th scope="col" class="font-semibold text-black">K&W Charge</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bag->ingredients as $ing)
            @php
            $position = $ing->position;
            $variant = $position->variant;
            $bottle = $position->bottle;
            $percentage = $variant->product->getPercentage($herb);
            @endphp
            <tr>
                <td scope="row">{{ $bottle->date->format('d.m.Y') }}</td>
                <td scope="row">{{ $variant->product->name }} {{ $variant->size }}g</td>
                <td scope="row">{{ $position->count }}</td>
                <td scope="row">{{ $position->count * $variant->size * $percentage / 100 }}g</td>
                <td scope="row">{{ $position->getCharge() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>Keine Abfüllungen gefunden.</p>
    @endif
</div>
{{-- Ausschuss --}}
<div class="p-3 my-3 space-y-1 border rounded-md">
    <h2 class="text-lg font-semibold">Ausschuss</h2>
    <hr class="mb-3">
    <p>Ausschuss gesamt: {{ $bag->trashed }}g</p>
</div>
{{-- Übersicht --}}
<div class="p-3 my-3 space-y-1 border rounded-md">
    <h2 class="text-lg font-semibold">Übersicht</h2>
    <hr class="mb-3">
    <div class="grid max-w-md grid-cols-2">
        <div>
            <p>Anfangsbestand:</p>
            <p>Verbraucht durch Abfüllungen:</p>
            <p>Ausschuss:</p>
            <p>Verbleibende Restmenge:</p>
        </div>
        <div class="text-right">
            <p>{{ $bag->size }}g</p>
            <p>{{ $bag->size - $bag->getCurrent() }}g</p>
            <p>{{ $bag->trashed }}g</p>
            <p>{{ $bag->getCurrentWithTrashed() }}g</p>
        </div>
    </div>
</div>
