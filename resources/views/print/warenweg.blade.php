@php
    use Illuminate\Support\Carbon;
    $period = ($dateFrom || $dateTo)
        ? (($dateFrom ? Carbon::parse($dateFrom)->format('d.m.Y') : '…').' – '.($dateTo ? Carbon::parse($dateTo)->format('d.m.Y') : '…'))
        : 'Gesamter Zeitraum';

    $docType = match ($mode) {
        'product' => 'Produktspezifikation & Rückverfolgung',
        'delivery' => 'Wareneingang',
        'herb' => 'Rohstoff-Übersicht',
        'filling' => 'Abfüllprotokoll',
        default => 'Rückverfolgungsnachweis',
    };
@endphp
<x-print.layout
    title="Warenweg / Rückverfolgung"
    :docType="$docType"
    :subject="$subjectType.': '.$subjectLabel"
    :period="$period"
    :business="$business"
    :printedAt="$printedAt"
>
    {{-- Compliance flags always at the top --}}
    @if (! empty($flags))
        <div class="callout warn">
            <div class="title">{{ count($flags) }} Auffälligkeit(en) festgestellt</div>
            <ul>
                @foreach ($flags as $flag)
                    <li>{{ $flag }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="callout ok-box">Keine Auffälligkeiten — alle geprüften Punkte erfüllt.</div>
    @endif

    @switch($mode)

        {{-- ============ Mode A: Gebinde / Charge ============ --}}
        @case('gebinde')
            @forelse ($rows as $row)
                @php
                    $g = fn ($v) => $v >= 1000 ? number_format($v / 1000, 2, ',', '.').' kg' : number_format($v, 0, ',', '.').' g';
                @endphp
                <div class="avoid-break" style="margin-bottom:20px;">
                    <div class="section-title">
                        {{ $row['herb'] }} · Charge {{ $row['charge'] }}
                        <span class="muted">· Gebinde-ID {{ $row['bag_id'] }}</span>
                        @if (! $row['bio']) <span class="bad">· KONVENTIONELL</span> @endif
                        @if ($row['emptied']) <span class="muted">· geleert/verworfen</span> @endif
                    </div>

                    <table class="kv" style="margin-bottom:10px;">
                        <tr>
                            <td class="k">Lieferant</td><td>{{ $row['supplier'] ?? '—' }}</td>
                            <td class="k">Lieferdatum</td><td>{{ $row['delivery_date']?->format('d.m.Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="k">Kontrollstelle</td><td>{{ $row['inspector'] ?? '—' }}</td>
                            <td class="k">Kontrollstellen-Nr.</td><td>{!! $row['oeko_code'] ?: '<span class="bad">fehlt</span>' !!}</td>
                        </tr>
                        <tr>
                            <td class="k">Spezifikation</td><td>{{ $row['specification'] }} · {{ $row['size'] }}</td>
                            <td class="k">Freigabe</td><td>{!! $row['released'] ? '<span class="ok">freigegeben</span>' : '<span class="bad">nicht freigegeben</span>' !!}</td>
                        </tr>
                        <tr>
                            <td class="k">MHD</td><td>{{ $row['bestbefore']?->format('d.m.Y') ?? '—' }}</td>
                            <td class="k">Dokumente</td>
                            <td>@foreach ($row['documents'] as $doc => $present)<span class="{{ $present ? 'ok' : 'bad' }}">{{ $present ? '✓' : '✗' }} {{ $doc }}</span>@if (! $loop->last) &nbsp;&nbsp; @endif @endforeach</td>
                        </tr>
                    </table>

                    {{-- Per-bag mass balance --}}
                    <div class="mini-title">Mengenbilanz Gebinde</div>
                    <table style="margin-bottom:10px;">
                        <thead>
                            <tr><th class="num">Eingang</th><th class="num">Verbraucht</th><th class="num">Verlust</th><th class="num">Restbestand</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="num">{{ $g($row['balance']['delivered']) }}</td>
                                <td class="num">{{ $g($row['balance']['used']) }}</td>
                                <td class="num">{{ $g($row['balance']['loss']) }}</td>
                                <td class="num" style="font-weight:700;">{{ $g($row['balance']['remaining']) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <x-print.checklist :checks="$row['checks']" />

                    @if ($row['usage']->isNotEmpty())
                        @php
                            $gramsFmt = fn ($g) => $g >= 1000
                                ? number_format($g / 1000, 2, ',', '.').' kg'
                                : number_format($g, 0, ',', '.').' g';
                            $pctFmt = fn ($p) => rtrim(rtrim(number_format($p, 1, ',', '.'), '0'), ',').' %';
                        @endphp
                        <div class="mini-title">Verwendung</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Produkt / Variante</th>
                                    <th>Charge</th>
                                    <th>Datum</th>
                                    <th class="num">Stück</th>
                                    <th class="num">Anteil</th>
                                    <th class="num">Verwendet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($row['usage'] as $use)
                                    <tr>
                                        <td>{{ $use['product'] }}@if ($use['size']) <span class="muted">· {{ $use['size'] }} g</span>@endif</td>
                                        <td class="mono">{{ $use['charge'] }}</td>
                                        <td>{{ $use['date']?->format('d.m.Y') ?? '—' }}</td>
                                        <td class="num">{{ $use['count'] }}</td>
                                        <td class="num">{{ $pctFmt($use['percentage']) }}</td>
                                        <td class="num">{{ $gramsFmt($use['grams']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="sum-row">
                                    <td colspan="3">Summe</td>
                                    <td class="num">{{ $row['usage']->sum('count') }}</td>
                                    <td class="num"></td>
                                    <td class="num">{{ $gramsFmt($row['usage']->sum('grams')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    @else
                        <div class="muted" style="font-style:italic; margin-top:6px;">Noch nicht in einer Abfüllung verwendet.</div>
                    @endif
                </div>
            @empty
                <p class="muted">Keine Daten für diese Auswahl.</p>
            @endforelse
            @break

        {{-- ============ Mode B: Lieferung ============ --}}
        @case('delivery')
            <table class="kv" style="margin:14px 0 6px;">
                <tr>
                    <td class="k">Lieferant</td><td>{{ $header['supplier'] ?? '—' }}</td>
                    <td class="k">Lieferdatum</td><td>{{ $header['delivery_date']?->format('d.m.Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="k">Kontrollstelle</td><td>{{ $header['inspector'] ?? '—' }}</td>
                    <td class="k">Kontrollstellen-Nr.</td><td>{!! $header['oeko_code'] ?: '<span class="bad">fehlt</span>' !!}</td>
                </tr>
                <tr>
                    <td class="k">Freigabe</td><td>{!! $header['released'] ? '<span class="ok">freigegeben</span>' : '<span class="bad">nicht freigegeben</span>' !!}</td>
                    <td class="k">Dokumente</td>
                    <td>@foreach ($header['documents'] as $doc => $present)<span class="{{ $present ? 'ok' : 'bad' }}">{{ $present ? '✓' : '✗' }} {{ $doc }}</span>@if (! $loop->last) &nbsp;&nbsp; @endif @endforeach</td>
                </tr>
            </table>

            <x-print.checklist :checks="$checks" />

            <div class="section-title">Gelieferte Gebinde ({{ $bags->count() }})</div>
            <table>
                <thead><tr><th>Rohstoff</th><th>Charge</th><th>Spezifikation</th><th class="num">Menge</th><th>MHD</th></tr></thead>
                <tbody>
                    @foreach ($bags as $bag)
                        <tr>
                            <td>{{ $bag['herb'] }} @if (! $bag['bio']) <span class="bad">(konv.)</span> @endif</td>
                            <td class="mono">{{ $bag['charge'] }}</td>
                            <td>{{ $bag['specification'] }}</td>
                            <td class="num">{{ $bag['size'] }}</td>
                            <td>{{ $bag['bestbefore']?->format('d.m.Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @break

        {{-- ============ Mode C: Rohstoff ============ --}}
        @case('herb')
            <table class="kv" style="margin:14px 0 6px;">
                <tr>
                    <td class="k">Rohstoff</td><td>{{ $subjectLabel }}</td>
                    <td class="k">Aktueller Bestand</td><td>{{ $herbStock }}</td>
                </tr>
                <tr>
                    <td class="k">Gebinde</td><td>{{ $gebinde->count() }}</td>
                    <td class="k">In Produkten</td><td>{{ $products->count() }}</td>
                </tr>
            </table>

            <div class="section-title">Gebinde dieses Rohstoffs</div>
            <table>
                <thead><tr><th>Charge</th><th>Lieferant</th><th>Kontr.-Nr.</th><th>Lieferdatum</th><th class="num">Menge</th><th>Freigabe</th></tr></thead>
                <tbody>
                    @forelse ($gebinde as $g)
                        <tr>
                            <td class="mono">{{ $g['charge'] }}</td>
                            <td>{{ $g['supplier'] ?? '—' }}</td>
                            <td>{!! $g['oeko_code'] ?: '<span class="bad">fehlt</span>' !!}</td>
                            <td>{{ $g['delivery_date']?->format('d.m.Y') ?? '—' }}</td>
                            <td class="num">{{ $g['size'] }}</td>
                            <td>{!! $g['released'] ? '<span class="ok">✓</span>' : '<span class="bad">✗</span>' !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Keine Gebinde im Zeitraum.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="section-title">Verwendet in Produkten</div>
            <table>
                <thead><tr><th>Produkt</th></tr></thead>
                <tbody>
                    @forelse ($products as $p)
                        <tr><td>{{ $p['product'] }}</td></tr>
                    @empty
                        <tr><td class="muted">Noch nicht verwendet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

        {{-- ============ Mode D: Produkt / Variante ============ --}}
        @case('product')
            <table class="kv" style="margin:14px 0 6px;">
                <tr>
                    <td class="k">Produkt</td><td>{{ $subjectLabel }}</td>
                    <td class="k">Typ</td><td>{{ $compound ? 'Mischung' : 'Einzelkraut' }}</td>
                </tr>
            </table>

            @if ($recipe->isNotEmpty())
                <div class="section-title">Rezeptur</div>
                <table>
                    <thead><tr><th>Rohstoff</th><th class="num">Anteil</th></tr></thead>
                    <tbody>
                        @foreach ($recipe as $ing)
                            <tr><td>{{ $ing['herb'] }}</td><td class="num">{{ rtrim(rtrim(number_format($ing['percentage'], 1, ',', '.'), '0'), ',') }} %</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($variants->isNotEmpty())
                <div class="section-title">Varianten</div>
                <table>
                    <thead><tr><th class="num">Größe</th><th>Bestellnummer</th><th class="num">Abfüllungen</th></tr></thead>
                    <tbody>
                        @foreach ($variants as $v)
                            <tr><td class="num">{{ $v['size'] }} g</td><td>{{ $v['ordernumber'] ?: '—' }}</td><td class="num">{{ $v['fillings'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <div class="section-title">Abfüllungen ({{ $bottlings->count() }})</div>
            @forelse ($bottlings as $b)
                <div class="avoid-break" style="margin-bottom:10px;">
                    <table class="kv" style="margin-bottom:2px;">
                        <tr>
                            <td class="k">Charge</td><td class="mono">{{ $b['charge'] }}</td>
                            <td class="k">Datum</td><td>{{ $b['date']?->format('d.m.Y') ?? '—' }}</td>
                            <td class="k">Variante</td><td>{{ $b['size'] ? $b['size'].' g' : '—' }}</td>
                            <td class="k">Stück</td><td>{{ $b['count'] }}</td>
                        </tr>
                    </table>
                    @if (! empty($b['bags']))
                        <table style="margin-bottom:6px;">
                            <thead><tr><th>Rohstoff</th><th>Charge</th><th>Lieferant</th><th>Kontr.-Nr.</th><th>Freigabe</th></tr></thead>
                            <tbody>
                                @foreach ($b['bags'] as $bag)
                                    <tr>
                                        <td>{{ $bag['herb'] }} @if (! $bag['bio']) <span class="bad">(konv.)</span> @endif</td>
                                        <td class="mono">{{ $bag['charge'] }}</td>
                                        <td>{{ $bag['supplier'] ?? '—' }}</td>
                                        <td>{!! $bag['oeko_code'] ?: '<span class="bad">fehlt</span>' !!}</td>
                                        <td>{!! $bag['released'] && $bag['certificate'] ? '<span class="ok">✓</span>' : '<span class="bad">✗</span>' !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @empty
                <p class="muted">Keine Abfüllungen im Zeitraum.</p>
            @endforelse
            @break

        {{-- ============ Mode E: Abfüllung ============ --}}
        @case('filling')
            <table class="kv" style="margin:14px 0 6px;">
                <tr>
                    <td class="k">Produkt</td><td>{{ $filling['product'] ?? '—' }}</td>
                    <td class="k">Variante</td><td>{{ $filling['size'] ? $filling['size'].' g' : '—' }}</td>
                </tr>
                <tr>
                    <td class="k">Charge</td><td class="mono">{{ $filling['charge'] }}</td>
                    <td class="k">Abgefüllt am</td><td>{{ $filling['date']?->format('d.m.Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="k">Menge</td><td>{{ $filling['count'] }} Stück</td>
                    <td class="k"></td><td></td>
                </tr>
            </table>

            <div class="section-title">Verwendete Rohstoffe (Gebinde)</div>
            <table>
                <thead><tr><th>Rohstoff</th><th>Gebinde-ID</th><th>Charge</th><th>Lieferant</th><th>Kontr.-Nr.</th><th>Lieferdatum</th><th>Freigabe</th></tr></thead>
                <tbody>
                    @forelse ($ingredients as $bag)
                        <tr>
                            <td>{{ $bag['herb'] }} @if (! $bag['bio']) <span class="bad">(konv.)</span> @endif</td>
                            <td class="mono">{{ $bag['bag_id'] }}</td>
                            <td class="mono">{{ $bag['charge'] }}</td>
                            <td>{{ $bag['supplier'] ?? '—' }}</td>
                            <td>{!! $bag['oeko_code'] ?: '<span class="bad">fehlt</span>' !!}</td>
                            <td>{{ $bag['delivery_date']?->format('d.m.Y') ?? '—' }}</td>
                            <td>{!! $bag['released'] && $bag['certificate'] ? '<span class="ok">✓</span>' : '<span class="bad">✗</span>' !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Keine Rohstoffe zugeordnet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @break

    @endswitch

    {{-- Physical-check signature --}}
    <div class="signatures avoid-break">
        <div class="field">
            <div class="line"></div>
            <div class="lbl">Geprüft am / durch (Datum, Name)</div>
        </div>
        <div class="field">
            <div class="line"></div>
            <div class="lbl">Unterschrift</div>
        </div>
    </div>
</x-print.layout>
