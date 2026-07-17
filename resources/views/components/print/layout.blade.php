@props([
    'title',
    'docType' => 'Dokument',
    'subject' => null,
    'period' => null,
    'business',
    'printedAt',
])
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        /* A4 with real document margins owned by CSS so HTML preview and PDF
           look identical. Browsershot is called with zero margins. */
        @page {
            size: A4;
            margin: 18mm 16mm 16mm 16mm;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #23241f;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* On-screen (?format=html) preview: paint a page so it reads like paper. */
        @media screen {
            body {
                background: #e9eae6;
                padding: 28px 0;
            }
            .sheet {
                width: 210mm;
                min-height: 297mm;
                margin: 0 auto;
                padding: 18mm 16mm 16mm;
                background: #fff;
                box-shadow: 0 2px 14px rgba(0, 0, 0, 0.14);
            }
        }
        @media print {
            .sheet { padding: 0; }
        }

        /* Letterhead */
        .letterhead {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
        }
        .letterhead .brand-name {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }
        .letterhead .brand-meta {
            font-size: 8.5px;
            color: #7c7d77;
            margin-top: 3px;
        }
        .letterhead .oeko-badge {
            border: 1.5px solid #a2bb94;
            border-radius: 5px;
            padding: 5px 10px;
            text-align: center;
            white-space: nowrap;
        }
        .letterhead .oeko-badge .lbl {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #7c7d77;
        }
        .letterhead .oeko-badge .code {
            font-size: 11px;
            font-weight: 700;
            color: #45592f;
        }

        .rule {
            height: 2px;
            background: #23241f;
            margin: 10px 0 0;
        }

        /* Document title + meta panel */
        .doc-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 24px;
            margin: 20px 0 6px;
        }
        .doc-head .doc-type {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #7c7d77;
        }
        .doc-head h1 {
            font-size: 19px;
            font-weight: 700;
            margin: 2px 0 0;
        }

        .meta-panel {
            border: 1px solid #d8d9d3;
            border-radius: 6px;
            padding: 8px 12px;
            min-width: 210px;
            background: #fafaf8;
        }
        .meta-panel .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 1.5px 0;
        }
        .meta-panel .k { color: #7c7d77; }
        .meta-panel .v { font-weight: 600; text-align: right; }

        .subject-line {
            margin: 12px 0 4px;
            font-size: 12px;
        }
        .subject-line .k {
            color: #7c7d77;
            font-weight: 400;
        }
        .subject-line .v {
            font-weight: 700;
            color: #35502a;
        }
        .period-line { font-size: 9.5px; color: #7c7d77; margin-bottom: 4px; }

        /* Sections + tables */
        .section-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #55564f;
            border-bottom: 1px solid #d7e2d0;
            padding-bottom: 4px;
            margin: 20px 0 9px;
        }
        .mini-title {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #7c7d77;
            font-weight: 700;
            margin: 10px 0 4px;
        }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #7c7d77;
            font-weight: 700;
            padding: 5px 7px;
            border-bottom: 1.5px solid #b9bab3;
        }
        tbody td {
            padding: 5px 7px;
            border-bottom: 0.5px solid #e7e7e3;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td { background: #fafaf8; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }

        tfoot td {
            padding: 5px 7px;
            border-top: 1.5px solid #b9bab3;
            font-weight: 700;
        }
        tfoot .sum-row td { background: #f2f3ef; }

        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 3px 7px; border-bottom: 0.5px solid #eeeee9; }
        .kv td.k { color: #7c7d77; width: 22%; }

        .ok { color: #3a6b2f; }
        .bad { color: #b3261e; font-weight: 700; }
        .muted { color: #7c7d77; }
        .mono { font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace; font-size: 9.5px; }

        .callout {
            border-radius: 6px;
            padding: 9px 13px;
            margin: 14px 0;
        }
        .callout.warn { border: 1.5px solid #e3b7b3; background: #fdf4f3; }
        .callout.warn .title { font-weight: 700; color: #b3261e; margin-bottom: 4px; }
        .callout.warn ul { margin: 3px 0 0; padding-left: 16px; }
        .callout.warn li { color: #8a2019; margin: 1px 0; }
        .callout.ok-box { border: 1.5px solid #a2bb94; background: #f6f8f5; color: #3a5a2f; font-weight: 600; }

        /* Signature + footer */
        .signatures {
            display: flex;
            gap: 40px;
            margin-top: 34px;
        }
        .signatures .field { flex: 1; }
        .signatures .line { border-bottom: 1px solid #23241f; height: 30px; }
        .signatures .lbl { font-size: 8px; color: #7c7d77; margin-top: 3px; }

        .footer {
            margin-top: 22px;
            border-top: 1px solid #d8d9d3;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            color: #9a9b94;
        }

        .avoid-break { break-inside: avoid; page-break-inside: avoid; }
    </style>
</head>
<body>
<div class="sheet">
    {{-- Letterhead --}}
    <div class="letterhead">
        <div>
            <div class="brand-name">{{ $business['name'] }}</div>
            <div class="brand-meta">{{ $business['owner'] }}</div>
            <div class="brand-meta">{{ $business['address']['street'] }} · {{ $business['address']['postal_code'] }} {{ $business['address']['city'] }}</div>
            <div class="brand-meta">{{ $business['contact']['email'] }} · {{ $business['contact']['website'] }}</div>
        </div>
        <div class="oeko-badge">
            <div class="lbl">Öko-Kontrollstelle</div>
            <div class="code">{{ $business['organic']['control_body_code'] }}</div>
            <div class="lbl" style="margin-top:3px;">Kontroll-Nr.</div>
            <div class="brand-meta" style="font-weight:600;">{{ $business['organic']['control_number'] }}</div>
        </div>
    </div>
    <div class="rule"></div>

    {{-- Document title + meta --}}
    <div class="doc-head">
        <div>
            <div class="doc-type">{{ $docType }}</div>
            <h1>{{ $title }}</h1>
        </div>
        <div class="meta-panel">
            <div class="row"><span class="k">Erstellt am</span><span class="v">{{ $printedAt->format('d.m.Y') }}</span></div>
            <div class="row"><span class="k">Uhrzeit</span><span class="v">{{ $printedAt->format('H:i') }} Uhr</span></div>
            <div class="row"><span class="k">Dokument-Nr.</span><span class="v mono">{{ $printedAt->format('ymd-Hi') }}</span></div>
        </div>
    </div>

    @if ($subject)
        <div class="subject-line"><span class="k">Betreff: </span><span class="v">{{ $subject }}</span></div>
    @endif
    @if ($period)
        <div class="period-line">Zeitraum: {{ $period }}</div>
    @endif

    {{ $slot }}

    <div class="footer">
        <span>{{ $business['name'] }} · {{ $business['organic']['control_body_code'] }} · {{ $business['organic']['control_number'] }}</span>
        <span>Interne Aufzeichnung — {{ $title }}</span>
    </div>
</div>
</body>
</html>
