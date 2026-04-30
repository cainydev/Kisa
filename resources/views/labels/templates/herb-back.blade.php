@php
    use App\Labels\BioMode;

    $headingColor = $headingColor ?? '#d8dc8e';
    $subtitleColor = $subtitleColor ?? '#6f7070';
    $textColor = $textColor ?? '#1c1d1c';
    $brand = config('labels.brand');
    $latin = trim((string) ($latinName ?? ''));
    $displayName = $displayName ?? $backTitle;
    $cutForm = trim((string) ($cutForm ?? ''));

    // Bio resolution. For Einzelkraut the recipe is the herb itself, so
    // `from_stock` looks at the herb's bags; `bio` and `none` are explicit
    // overrides. The herb is implicitly the only one in `entity->herbs`.
    $bioMode = BioMode::tryFrom((string) ($bioMode ?? '')) ?? BioMode::Bio;
    $isBio = (function () use ($bioMode, $entity) {
        if ($bioMode === BioMode::Bio) {
            return true;
        }
        if ($bioMode === BioMode::None || ! $entity) {
            return false;
        }
        // from_stock — walk the (single) herb's bags
        foreach ($entity->herbs ?? [] as $herb) {
            if ($bioMode->herbIsBio($herb)) {
                return true;
            }
        }

        return false;
    })();
    // Single-ingredient labels can show seals if the herb qualifies as bio.
    $bioSealsAllowed = $isBio;

    // Build the brewing-instructions paragraph from the resolved display name
    // and steep time when no override is set. The display-name fallback means
    // setting `displayName = "Angelikawurzel"` on a label flows through here
    // automatically. Run through the same hyphenator that the param resolver
    // uses so the auto-built fallback gets soft hyphens just like an override.
    $preparationBodyText = (! empty($preparationBody))
        ? $preparationBody
        : app(\App\Labels\Hyphenator::class)->hyphenate(
            '1-2 Teelöffel '.$displayName.' mit ca. 250 ml siedendem Wasser übergießen und nach '.($prepTime ?? '5-8 Min.').' abseihen.'
        );

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

    $bioSealSrc = $bioSealsAllowed ? $imgSrc($bioSeal ?? null) : null;
    $gruenPunktSrc = $imgSrc($gruenPunkt ?? null);
    $euBioLeafSrc = $bioSealsAllowed ? $imgSrc($euBioLeaf ?? null) : null;
    $prepAmountIconSrc = $imgSrc($prepAmountIcon ?? null);
    $prepTemperatureIconSrc = $imgSrc($prepTemperatureIcon ?? null);
    $prepTimeIconSrc = $imgSrc($prepTimeIcon ?? null);

    $titleFontFace = $fontFace($titleFont ?? null, 'herb-title');
    $bodyFontFace = $fontFace($bodyFont ?? null, 'herb-body');
    $italicFontFace = $fontFace($italicFont ?? null, 'herb-italic');
    $subtitleFontFace = $fontFace($subtitleFont ?? null, 'herb-subtitle');
    $accentFontFace = $fontFace($accentFont ?? null, 'herb-accent');
@endphp
<x-label-page :width="$width" :height="$height" :bleed="$bleed" :marks="$marks" :slug="$slug ?? null">
    <style>
        {!! $titleFontFace !!}
        {!! $bodyFontFace !!}
        {!! $italicFontFace !!}
        {!! $subtitleFontFace !!}
        {!! $accentFontFace !!}
        .herb-back {
            position: relative;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            padding: 5mm 5mm;
            color: {{ $textColor }};
            font-family: 'herb-body', -apple-system, sans-serif;
            font-size: 3.5mm;
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
            line-height: 1.1;
            hyphens: auto;
            -webkit-hyphens: auto;
            hyphenate-limit-chars: 6 3 3;
        }
        .herb-back .latin { font-family: 'herb-italic', 'herb-body', -apple-system, sans-serif; font-style: italic; }
        /* Shared heading style: "Inhaltsstoffe:", "Zubereitungshinweise:",
           "Sicherheitshinweis:" — same typography regardless of position. */
        .herb-back .section-heading {
            font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
            font-size: 3.5mm;
            line-height: 1;
        }
        .herb-back h3.section-heading {
            margin: 0;
        }
        .herb-back .prep-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin: -1.5mm 0 2.5mm 0;
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
            height: 25mm;
            margin-bottom: 2.5mm;
        }
        .herb-back .prep-row .icon img,
        .herb-back .prep-row .icon svg {
            width: auto;
            height: 25mm;
            object-fit: contain;
            display: block;
        }
        .herb-back .prep-row .caption {
            font-family: 'herb-accent', 'herb-body', -apple-system, sans-serif;
            font-size: 3.88mm;
            line-height: 1;
            text-align: center;
            color: {{ $textColor }};
        }
        .herb-back .preparation-body {
            margin: 0 0 2.5mm 0;
            line-height: 1.1;
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
            hyphenate-limit-chars: 6 3 3;
        }
        .herb-back .preparation-2-title {
            margin-top: 1.5mm;
        }
        .herb-back .usage-note {
            margin: 0 0 2mm 0;
            line-height: 1.1;
        }
        .herb-back .safety-hint {
            margin: 0;
            line-height: 1.1;
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
            hyphenate-limit-chars: 6 3 3;
        }
        .herb-back .seals-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 2.5mm 0 2mm 0;
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
            line-height: 0;
        }
        .herb-back .seals-row .seals img {
            height: 12mm;
            width: auto;
            object-fit: contain;
            display: block;
        }
        .herb-back .seals-row .seals .bio-seal {
            height: 13mm;
        }
        .herb-back .seals-row .eu-seal {
            position: relative;
            display: flex;
            align-items: center;
            line-height: 0;
        }
        .herb-back .seals-row .eu-seal .oeko-cap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: -4.2mm;
            font-size: 1.41mm;
            line-height: 1.1;
            text-align: left;
            color: {{ $textColor }};
        }
        .herb-back .bottom-bar {
            position: relative;
            margin-top: auto;
            margin-left: auto;
            margin-right: auto;
            width: 50.20mm;
            height: 0.50mm;
            background: #d8d8d8;
            border-radius: 0.25mm;
        }
        .herb-back .bottom-bar .sticker-outline {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 0;
            width: 95mm;
            height: 48mm;
            background: rgba(255, 0, 128, 0.12);
            border: 0.3mm dashed #ff0080;
            pointer-events: none;
            z-index: 99;
        }
        .herb-back .bottom-bar .sticker-outline .label {
            position: absolute;
            top: 1mm;
            left: 1mm;
            font-size: 2.5mm;
            color: #ff0080;
            font-family: -apple-system, sans-serif;
        }
    </style>
    <div class="herb-back">
        <h1 class="title">{{ $backTitle }}</h1>

        <p class="ingredients">
            <span class="section-heading">Inhaltsstoffe:</span>
            @if ($isBio)Bio @endif{{ $displayName }}@if ($cutForm !== '') {{ $cutForm }}@endif@if ($latin) (<span class="latin">{{ $latin }}</span>)@endif@if ($isBio) aus kontrolliert biologischem Anbau aus {{ $brand['oeko_origin'] ?? 'EU-/Nicht-EU-Landwirtschaft' }} {{ $brand['oeko_code'] ?? 'DE-ÖKO-039' }}@endif
        </p>

        @if (! empty($usageNote))
            <p class="usage-note"><span class="section-heading">Anwendung:</span> {{ $usageNote }}</p>
        @endif

        <h3 class="section-heading">{{ $preparationTitle ?? 'Zubereitungshinweise:' }}</h3>
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

        <p class="preparation-body">{{ $preparationBodyText }}</p>

        @if (! empty($preparation2Body))
            @if (! empty($preparation2Title))
                <h3 class="section-heading preparation-2-title">{{ $preparation2Title }}</h3>
            @endif
            <p class="preparation-body">{{ $preparation2Body }}</p>
        @endif

        <p class="safety-hint"><span class="section-heading">Sicherheitshinweis:</span> {{ $safetyHint }}</p>

        <div class="seals-row">
            <div class="fill-hint">{{ $fillVolumeHint }}</div>
            <div class="seals">
                @if ($gruenPunktSrc) <img src="{{ $gruenPunktSrc }}" class="gruen-punkt" alt=""> @endif
                @if ($bioSealSrc) <img src="{{ $bioSealSrc }}" class="bio-seal" alt=""> @endif
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

        <div class="bottom-bar">
            @if (! empty($showStickerOutline))
                <div class="sticker-outline">
                    <span class="label">Aufkleber 95×48 mm</span>
                </div>
            @endif
        </div>
    </div>
</x-label-page>
