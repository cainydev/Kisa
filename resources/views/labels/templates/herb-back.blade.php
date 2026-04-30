@php
    $headingColor = $headingColor ?? '#d8dc8e';
    $subtitleColor = $subtitleColor ?? '#6f7070';
    $textColor = $textColor ?? '#1c1d1c';
    $brand = config('labels.brand');
    $latin = trim((string) ($latinName ?? ''));
    $displayName = $displayName ?? $title;

    $imgSrc = function ($media) {
        if (!$media || !is_file($media->getPath())) {
            return null;
        }
        $mime = $media->mime_type ?: mime_content_type($media->getPath());
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($media->getPath()));
    };

    $fontFace = function ($media, string $family): ?string {
        if (!$media || !is_file($media->getPath())) {
            return null;
        }
        $ext = strtolower(pathinfo($media->getPath(), PATHINFO_EXTENSION));
        $format = match ($ext) {
            'otf' => 'opentype',
            'ttf' => 'truetype',
            'woff' => 'woff',
            'woff2' => 'woff2',
            default => 'opentype',
        };
        $mime = $media->mime_type ?: 'font/' . $ext;
        $data = base64_encode(file_get_contents($media->getPath()));
        return "@font-face { font-family: '{$family}'; font-display: block; src: url(data:{$mime};base64,{$data}) format('{$format}'); }";
    };

    $bioSealSrc = $imgSrc($bioSeal ?? null);
    $gruenPunktSrc = $imgSrc($gruenPunkt ?? null);
    $euBioLeafSrc = $imgSrc($euBioLeaf ?? null);
    $prepAmountIconSrc = $imgSrc($prepAmountIcon ?? null);
    $prepTemperatureIconSrc = $imgSrc($prepTemperatureIcon ?? null);
    $prepTimeIconSrc = $imgSrc($prepTimeIcon ?? null);

    $titleFontFace = $fontFace($titleFont ?? null, 'herb-title');
    $bodyFontFace = $fontFace($bodyFont ?? null, 'herb-body');
    $italicFontFace = $fontFace($italicFont ?? null, 'herb-italic');
    $subtitleFontFace = $fontFace($subtitleFont ?? null, 'herb-subtitle');
    $accentFontFace = $fontFace($accentFont ?? null, 'herb-accent');
    $brandFontFace = $fontFace($brandFont ?? null, 'herb-brand');
@endphp
<x-label-page :width="$width" :height="$height" :bleed="$bleed" :marks="$marks" :slug="$slug ?? null">
    <style>
        {!! $titleFontFace !!}
        {!! $bodyFontFace !!}
        {!! $italicFontFace !!}
        {!! $subtitleFontFace !!}
        {!! $accentFontFace !!}
        {!! $brandFontFace !!}
        .herb-back {
            position: relative;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            padding: 7mm 7mm;
            color: {{ $textColor }};
            font-family: 'herb-body', -apple-system, sans-serif;
            font-size: 3.18mm;
            line-height: 1;
            display: flex;
            flex-direction: column;
        }
        .herb-back .title {
            font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
            font-size: 6mm;
            line-height: 1;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: {{ $headingColor }};
            margin: 0 0 1.25mm 0;
        }
        .herb-back .ingredients {
            margin: 0 0 4mm 0;
            line-height: 1;
            text-align: justify;
        }
        .herb-back .label { font-family: 'herb-title', 'herb-body', -apple-system, sans-serif; }
        .herb-back .latin { font-family: 'herb-italic', 'herb-body', -apple-system, sans-serif; font-style: italic; }
        .herb-back h3 {
            font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
            font-size: 3.18mm;
            line-height: 1;
            margin: 0 0 1.5mm 0;
        }
        .herb-back .prep-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin: 0 0 6.07mm 0;
        }
        .herb-back .prep-row .item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .herb-back .prep-row .icon {
            display: flex;
            align-items: end;
            justify-content: center;
            height: 24mm;
            margin-bottom: 2mm;
        }
        .herb-back .prep-row .icon img {
            width: auto;
            height: 24mm;
            object-fit: contain;
        }
        .herb-back .prep-row .caption {
            font-family: 'herb-accent', 'herb-body', -apple-system, sans-serif;
            font-size: 3.88mm;
            line-height: 1;
            text-align: center;
            color: {{ $textColor }};
        }
        .herb-back .preparation-body { margin: 0 0 6mm 0; line-height: 1; text-align: justify; }
        .herb-back .safety-hint { margin: 0; line-height: 1; text-align: justify; }
        .herb-back .seals-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 6mm 0 4mm 0;
        }
        .herb-back .seals-row .fill-hint {
            font-size: 2.82mm;
            line-height: 1.2;
            max-width: 36mm;
        }
        .herb-back .seals-row .seals {
            display: flex;
            align-items: center;
            gap: 2mm;
        }
        .herb-back .seals-row .seals img {
            height: 12mm;
            width: auto;
            object-fit: contain;
        }
        .herb-back .seals-row .seals .gruen-punkt {
            filter: saturate(0.55) brightness(1.08);
        }
        .herb-back .seals-row .eu-seal {
            position: relative;
        }
        .herb-back .seals-row .eu-seal .oeko-cap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: -3mm;
            font-size: 1.41mm;
            line-height: 1.1;
            text-align: left;
            color: {{ $textColor }};
        }
        .herb-back .address-block {
            font-size: 3.53mm;
            line-height: 1.2;
            text-align: left;
        }
        .herb-back .address-block .brand-name {
            font-family: 'herb-brand', 'herb-title', 'herb-body', -apple-system, sans-serif;
            letter-spacing: 0.03em;
        }
        .herb-back .bottom-bar {
            margin-top: auto;
            margin-left: auto;
            margin-right: auto;
            width: 50.20mm;
            height: 0.80mm;
            background: {{ $headingColor }};
        }
    </style>
    <div class="herb-back">
        <h1 class="title">{{ $title }}</h1>

        <p class="ingredients">
            <span class="label">Inhaltsstoffe:</span>
            Bio {{ $displayName }}@if ($latin) (<span class="latin">{{ $latin }}</span>)@endif<br>
            aus kontrolliert biologischem Anbau aus {{ $brand['oeko_origin'] ?? 'EU-/Nicht-EU-Landwirtschaft' }} {{ $brand['oeko_code'] ?? 'DE-ÖKO-039' }}
        </p>

        <h3>Zubereitungshinweise:</h3>
        <div class="prep-row">
            <div class="item">
                <div class="icon">@if ($prepAmountIconSrc)<img src="{{ $prepAmountIconSrc }}" alt="">@endif</div>
                <div class="caption">{{ $prepAmount }}</div>
            </div>
            <div class="item">
                <div class="icon">@if ($prepTemperatureIconSrc)<img src="{{ $prepTemperatureIconSrc }}" alt="">@endif</div>
                <div class="caption">{{ $prepTemperature }}</div>
            </div>
            <div class="item">
                <div class="icon">@if ($prepTimeIconSrc)<img src="{{ $prepTimeIconSrc }}" alt="">@endif</div>
                <div class="caption">{{ $prepTime }}</div>
            </div>
        </div>

        <p class="preparation-body">{{ $preparationBody }}</p>

        <p class="safety-hint"><span class="label">Sicherheitshinweis:</span> {{ $safetyHint }}</p>

        <div class="seals-row">
            <div class="fill-hint">{{ $fillVolumeHint }}</div>
            <div class="seals">
                @if ($gruenPunktSrc) <img src="{{ $gruenPunktSrc }}" class="gruen-punkt" alt=""> @endif
                @if ($bioSealSrc) <img src="{{ $bioSealSrc }}" alt=""> @endif
                @if ($euBioLeafSrc)
                    <div class="eu-seal">
                        <img src="{{ $euBioLeafSrc }}" alt="">
                        <div class="oeko-cap">
                            {{ $brand['oeko_code'] ?? 'DE-ÖKO-039' }} · {{ $brand['oeko_origin'] ?? 'EU-/Nicht-EU-Landwirtschaft' }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="address-block">
            <div class="brand-name">{{ $brand['name'] ?? 'kräuter & wege GbR' }}</div>
            @foreach ($brand['address_lines'] ?? [] as $line)
                <div>{{ $line }}</div>
            @endforeach
        </div>

        <div class="bottom-bar"></div>
    </div>
</x-label-page>
