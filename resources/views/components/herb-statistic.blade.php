@props(['bag'])
@php
$herb = $bag->herb;
@endphp

<style>
    table.puretable {
        background-color: #EEEEEE;
        width: 100%;
        border-radius: 0.5rem;
        overflow: hidden;
        text-align: left;
        border-collapse: collapse;
    }

    table.puretable td,
    table.puretable th {
        padding: 8px 8px;
    }

    table.puretable td {
        font-size: 13px;
    }

    table.puretable tr:nth-child(even) {
        background: #D0E4F5;
    }

    table.puretable .highlight {
        background: #1C6EA4;
        background: -moz-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
        background: -webkit-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
        background: linear-gradient(to bottom, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
        font-size: 15px;
        font-weight: bold;
        color: #FFFFFF;
        padding: 4px 3px;
    }

    table.puretable td.highlight {
        font-size: 15px;
    }
</style>

{{-- General --}}
<div style="font-family: sans-serif">
    <div class="p-3 my-3 space-y-1 border rounded-md">
        <h2 class="text-lg font-semibold">Allgemein</h2>
        <hr class="mb-3">
        <p>Produkt: {{ $herb->name }}</p>
        <p>Charge: {{ $bag->charge }}</p>
        <p>Wareneingang Menge: {{ $bag->size }}g</p>
        <p>Wareneingang Datum: {{ $bag->delivery->delivered_date->format('d.m.Y'); }}</p>
        <p>Lieferant: {{ $bag->delivery->supplier->company }}</p>
        <a class="mt-3 text-blue-500 underline print:hidden"
           href="{{ route('platform.deliveries.edit', $bag->delivery) }}">Link zur Lieferung</a>
    </div>
    {{-- Abfüllungen --}}
    <div class="p-3 my-3 space-y-1 border rounded-md">
        <h2 class="text-lg font-semibold">Abfüllungen</h2>
        <hr class="mb-3">
        @if($bag->ingredients->count() > 0)
        <table class="puretable">
            <tbody>
                <tr>
                    <th class="highlight">Datum</th>
                    <th class="highlight">Produkt</th>
                    <th class="highlight">Einheiten</th>
                    <th class="highlight">Verwendete Menge</th>
                    <th class="highlight">Charge</th>
                </tr>
                @foreach($bag->getIngredientsWithRelations() as $ing)

                @php
                $position = $ing->position;
                $bottle = $position->bottle;
                $variant = $position->variant;
                $product = $variant->product;

                $percentage = $product->getPercentage($herb);
                @endphp

                <tr>
                    <td>{{ $bottle->date->format('d.m.Y') }}</td>
                    <td>{{ $variant->product->name }} {{ $variant->size }}g</td>
                    <td>{{ $position->count }}</td>
                    <td>{{ $position->count * $variant->size * $percentage / 100 }}g</td>
                    <td>{{ $position->charge }}</td>
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
        <table class="puretable"
               style="width:100%;">
            <tr>
                <td style="font-weight:bold">Anfangsbestand:</td>
                <td>{{ $bag->size }}g</td>
            </tr>
            <tr>
                <td style="font-weight:bold">Verbrauch Mischungen:</td>
                <td>{{ $bag->getCompoundUsage() }}g</td>
            </tr>
            <tr>
                <td style="font-weight:bold">Verbrauch Einzel:</td>
                <td>{{ $bag->getNonCompoundUsage() }}g</td>
            </tr>
            <tr>
                <td style="font-weight:bold">Verbrauch Gesamt:</td>
                <td>{{ $bag->size - $bag->getCurrent() }}g</td>
            </tr>
            <tr>
                <td style="font-weight:bold">Ausschuss:</td>
                <td>{{ $bag->trashed }}g</td>
            </tr>
            <tr>
                <td style="font-weight:bold">Verbleibende Restmenge:</td>
                <td>{{ $bag->getCurrentWithTrashed() }}g</td>
            </tr>
        </table>
    </div>
</div>
