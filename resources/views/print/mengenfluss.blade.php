@php
    use Illuminate\Support\Carbon;
    $kg = fn ($g) => number_format($g / 1000, 1, ',', '.').' kg';
    $period = ($dateFrom || $dateTo)
        ? (($dateFrom ? Carbon::parse($dateFrom)->format('d.m.Y') : '…').' – '.($dateTo ? Carbon::parse($dateTo)->format('d.m.Y') : '…'))
        : 'Gesamter Zeitraum';
@endphp
<x-print.layout
    title="Mengenflussrechnung"
    docType="Warenstrombilanz"
    :period="$period"
    :business="$business"
    :printedAt="$printedAt"
>
    {{-- Totals panel --}}
    <table class="kv" style="margin:14px 0 6px;">
        <tr>
            <td class="k">Rohstoffe</td>
            <td>{{ number_format($totals['herbs'], 0, ',', '.') }}</td>
            <td class="k">Eingang</td>
            <td>{{ $kg($totals['delivered']) }}</td>
            <td class="k">Verbrauch</td>
            <td>{{ $kg($totals['used']) }}</td>
        </tr>
        <tr>
            <td class="k">Verlust</td>
            <td>{{ $kg($totals['trashed']) }}</td>
            <td class="k">Bestand</td>
            <td>{{ $kg($totals['stock']) }}</td>
            <td class="k">Auffällig</td>
            <td>{!! $totals['implausible'] > 0 ? '<span class="bad">'.$totals['implausible'].'</span>' : '<span class="ok">0</span>' !!}</td>
        </tr>
    </table>

    @if ($totals['implausible'] > 0)
        <div class="callout warn">
            <div class="title">{{ $totals['implausible'] }} Rohstoff(e) mit Mengendifferenz</div>
            <div style="color:#8a2019;">Verbrauch + Verlust übersteigt den Eingang im Zeitraum.</div>
        </div>
    @else
        <div class="callout ok-box">Alle Rohstoffe mengenmäßig plausibel.</div>
    @endif

    <div class="section-title">Warenstrombilanz je Rohstoff</div>
    <table>
        <thead>
            <tr>
                <th>Rohstoff</th>
                <th class="num">Eingang</th>
                <th class="num">Verbrauch</th>
                <th class="num">Verlust</th>
                <th class="num">Bestand</th>
                <th class="num">Bilanz</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>
                        @if (! $row['plausible']) <span class="bad">!</span> @endif
                        {{ $row['herb'] }}
                    </td>
                    <td class="num">{{ $kg($row['delivered']) }}</td>
                    <td class="num">{{ $kg($row['used']) }}</td>
                    <td class="num">{{ $kg($row['trashed']) }}</td>
                    <td class="num">{{ $kg($row['stock']) }}</td>
                    <td class="num {{ $row['plausible'] ? 'ok' : 'bad' }}">
                        {{ $row['balance'] >= 0 ? '+' : '−' }}{{ $kg(abs($row['balance'])) }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Keine Bewegungen im gewählten Zeitraum.</td></tr>
            @endforelse
        </tbody>
    </table>

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
