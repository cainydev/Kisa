@props(['product'])

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
        <p>Produkt: {{ $product->name }}</p>
        <p>SKU (shopware): {{ $product->mainnumber }}</p>
        <p>Produkttyp: {{ $product->type->name }}</p>
        <p>Varianten: @foreach($product->variants as $v) {{ $v->size . 'g' . ($loop->last ? '' : ', ')}} @endforeach</p>
        <a class="mt-3 text-blue-500 underline print:hidden"
           href="{{ route('platform.products.edit', $product) }}">Link zum Produkt</a>
    </div>
    {{-- Abfüllungen per variant --}}
    <div class="p-3 my-3 space-y-1 border rounded-md">
        <h2 class="text-lg font-semibold">Abfüllungen pro Variante</h2>
        <hr class="mb-3">
        @forelse($product->variants as $variant)
        <h3 class="text-lg">{{ $variant->size }}g Variante</h3>
        <table class="puretable">
            <tbody>
                <tr>
                    <th class="highlight">Datum</th>
                    <th class="highlight">Anzahl</th>
                    <th class="highlight">Verwendete Menge</th>
                </tr>
                @foreach($variant->positions as $position)

                @php
                $bottle = $position->bottle;
                @endphp

                <tr>
                    <td class="underline"><a href="{{ route('platform.bottle.edit', $bottle) }}">Abfüllung vom {{
                            $bottle->date->format('d.m.Y') }}</a></td>
                    <td>{{ $position->count }}</td>
                    <td>{{ $position->count * $variant->size}}g</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @empty
        <p>Keine Varianten gefunden.</p>
        @endforelse
    </div>

    {{-- Übersicht
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
    </div>--}}
</div>
