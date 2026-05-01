@php
    use App\Labels\BioMode;
    use App\Labels\IngredientList;

    $subtitleColor = $subtitleColor ?? '#6f7070';
    $textColor = $textColor ?? '#1c1d1c';
    $brand = config('labels.brand');
    $bioMode = BioMode::tryFrom((string) ($bioMode ?? '')) ?? BioMode::FromStock;

    $list = IngredientList::build($entity ?? null, $bioMode);
    $inhaltsstoffeText = (! empty($inhaltsstoffe)) ? $inhaltsstoffe : $list->text;
    // anyBio drives the asterisk-and-footnote pattern. allBio swaps it for a
    // single closing sentence inline. bioSealsAllowed gates the BIO/EU-leaf
    // seals (≤ 5 % non-bio share, EU 95/5 rule).
    $isBio = $list->anyBio;
    $allBio = $list->allBio || ($bioMode === BioMode::Bio && $list->text !== '');
    $bioSealsAllowed = $isBio && $list->nonBioPercent <= 5.0;

    /**
     * Back-page name: the product name with a "Ruths " prefix unless it
     * already starts with one. The front page uses the bare name, so the
     * prefix lives only on the back.
     */
    $backTitle = (function (?string $t) {
        $name = trim((string) $t);
        if ($name === '' || stripos($name, 'Ruths') === 0) {
            return $name;
        }

        return 'Ruths '.$name;
    })($title ?? null);

    /**
     * Expand abbreviations like "5 Min." → "5 Minuten" for use in body text.
     * The captions under the prep icons keep the abbreviation; this is only
     * used when the value lands inside the brewing-instructions paragraph.
     */
    $expandPrepTime = function (?string $t): string {
        $t = trim((string) $t);
        if ($t === '') {
            return '';
        }

        // "5 Min." / "5 Min" / "5 min." → "5 Minuten" (consume the period too).
        return preg_replace('/\bMin\b\.?/iu', 'Minuten', $t);
    };

    /**
     * Build the brewing-instructions paragraph from the prep params.
     * Anything ending at 100°C (incl. ranges like "90-100°C") reads as
     * "siedendem Wasser"; lower temperatures read literally as "ca. {temp}
     * heißem Wasser". "Min." is expanded to "Minuten" inside the sentence.
     */
    $buildPreparationBody = function (string $name, ?string $amount, ?string $temp, ?string $time) use ($expandPrepTime) {
        $tempStr = trim((string) $temp);
        preg_match_all('/\d+/', $tempStr, $matches);
        $maxTemp = ! empty($matches[0]) ? (int) max($matches[0]) : null;
        $waterClause = ($maxTemp === 100)
            ? 'siedendem Wasser'
            : 'ca. '.$tempStr.' heißem Wasser';
        $amountStr = preg_replace('/\s*TL\s*$/iu', '', trim((string) $amount));
        $amountClause = $amountStr !== '' ? $amountStr : '1';
        $timeClause = $expandPrepTime($time);
        $namePart = $name !== '' ? ' '.$name : '';

        return "{$amountClause} Teelöffel{$namePart} mit ca. 250 ml {$waterClause} übergießen"
            .($timeClause !== '' ? " und nach {$timeClause} abseihen." : ' und nach 5-8 Minuten abseihen.');
    };

    // Tokens supported in any preparationBody override:
    //   {prepAmount}    → e.g. "1-2"
    //   {prepTime}      → e.g. "5-8 Min."
    //   {prepTimeLong}  → "5-8 Min." → "5-8 Minuten"
    //   {displayName}   → e.g. "Frauen!Power"
    $prepAmountForBody = preg_replace('/\s*TL\s*$/iu', '', trim((string) ($prepAmount ?? '')));
    $prepTimeLong = $expandPrepTime($prepTime ?? '');
    $tokens = [
        '{prepAmount}' => $prepAmountForBody !== '' ? $prepAmountForBody : '1',
        '{prepTime}' => $prepTime ?? '5-8 Min.',
        '{prepTimeLong}' => $prepTimeLong !== '' ? $prepTimeLong : '5-8 Minuten',
        '{displayName}' => $backTitle ?? '',
    ];

    // Strip stored soft hyphens so {token} placeholders match, run substitution,
    // then re-hyphenate the final text so the output is line-break-friendly.
    $stripSoftHyphens = fn (?string $s) => str_replace("\u{00AD}", '', (string) $s);
    $preparationBodyText = (! empty($preparationBody))
        ? app(\App\Labels\Hyphenator::class)->hyphenate(strtr($stripSoftHyphens($preparationBody), $tokens))
        : $buildPreparationBody($backTitle, $prepAmount ?? null, $prepTemperature ?? null, $prepTime ?? null);

    // Captions under the prep icons. Operators may type "\n" in the field to
    // force a line break — convert it to <br> while escaping the rest.
    $renderCaption = function (?string $value): string {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $escaped = e($value);
        return str_replace(['\\n', "\n"], '<br>', $escaped);
    };

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

    $accentColor = $accentColor ?? '#C5C95C';
    $accentBaseColor = '#C5C95C';

    $svgInline = function ($media) use ($accentColor, $accentBaseColor): ?string {
        if (! $media || ! is_file($media->getPath())) {
            return null;
        }
        $svg = file_get_contents($media->getPath());
        $svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
        $svg = preg_replace('/<!DOCTYPE[^>]*>/', '', $svg);
        $pattern = '/' . preg_quote($accentBaseColor, '/') . '/i';

        return preg_replace($pattern, $accentColor, $svg);
    };

    $bioSealSrc = $bioSealsAllowed ? $imgSrc($bioSeal ?? null) : null;
    $gruenPunktSrc = $imgSrc($gruenPunkt ?? null);
    $euBioLeafSrc = $bioSealsAllowed ? $imgSrc($euBioLeaf ?? null) : null;
    $prepAmountIconSvg = $svgInline($prepAmountIcon ?? null);
    $prepTemperatureIconSvg = $svgInline($prepTemperatureIcon ?? null);
    $prepTimeIconSvg = $svgInline($prepTimeIcon ?? null);

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
        .ruths-blend-back {
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
        .ruths-blend-back .title {
            font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
            font-size: 6mm;
            line-height: 1;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: {{ $accentColor }};
            margin: 0 0 1.25mm 0;
        }
        .ruths-blend-back .ingredients {
            margin: 0 0 4mm 0;
            font-family: 'herb-body', -apple-system, sans-serif;
            font-size: 2.82mm;
            line-height: 1.125;
            hyphens: auto;
            -webkit-hyphens: auto;
            hyphenate-limit-chars: 6 3 3;
            overflow-wrap: break-word;
        }
        .ruths-blend-back .ingredients .bio-claim {
            display: block;
            margin-top: 1.5mm;
            font-family: 'herb-body', -apple-system, sans-serif;
            font-size: 2.82mm;
            line-height: 1.125;
        }
        .ruths-blend-back .latin { font-family: 'herb-italic', 'herb-body', -apple-system, sans-serif; font-style: italic; }
        /* Shared heading style: "Inhaltsstoffe:", "Zubereitungshinweise:",
           "Sicherheitshinweis:" — same typography regardless of position. */
        .ruths-blend-back .section-heading {
            font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
            font-size: 3.5mm;
            line-height: 1;
        }
        .ruths-blend-back h3.section-heading {
            margin: 0;
        }
        .ruths-blend-back .prep-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin: -1.5mm 0 2.5mm 0;
        }
        .ruths-blend-back .prep-row .item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .ruths-blend-back .prep-row .icon {
            display: flex;
            align-items: end;
            justify-content: center;
            height: 25mm;
            margin-bottom: 2.5mm;
        }
        .ruths-blend-back .prep-row .icon img,
        .ruths-blend-back .prep-row .icon svg {
            width: auto;
            height: 25mm;
            object-fit: contain;
            display: block;
        }
        .ruths-blend-back .prep-row .caption {
            font-family: 'herb-accent', 'herb-body', -apple-system, sans-serif;
            font-size: 3.88mm;
            line-height: 1;
            text-align: center;
            color: {{ $textColor }};
        }
        .ruths-blend-back .preparation-body {
            margin: 0 0 2.5mm 0;
            line-height: 1.1;
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
            hyphenate-limit-chars: 6 3 3;
        }
        .ruths-blend-back .safety-hint {
            margin: 0;
            line-height: 1.1;
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
            hyphenate-limit-chars: 6 3 3;
        }
        .ruths-blend-back .seals-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 2.5mm 0 2mm 0;
        }
        .ruths-blend-back .seals-row .fill-hint {
            font-size: 2.82mm;
            line-height: 1.2;
            max-width: 36mm;
        }
        .ruths-blend-back .seals-row .seals {
            display: flex;
            align-items: center;
            gap: 2mm;
            line-height: 0;
        }
        .ruths-blend-back .seals-row .seals img {
            height: 12mm;
            width: auto;
            object-fit: contain;
            display: block;
        }
        .ruths-blend-back .seals-row .seals .bio-seal {
            height: 13mm;
        }
        .ruths-blend-back .seals-row .eu-seal {
            position: relative;
            display: flex;
            align-items: center;
            line-height: 0;
        }
        .ruths-blend-back .seals-row .eu-seal .oeko-cap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: -4.2mm;
            font-size: 1.41mm;
            line-height: 1.1;
            text-align: left;
            color: {{ $textColor }};
        }
        .ruths-blend-back .bottom-bar {
            position: relative;
            margin-top: auto;
            margin-left: auto;
            margin-right: auto;
            width: 50.20mm;
            height: 0.50mm;
            background: #d8d8d8;
            border-radius: 0.25mm;
        }
        .ruths-blend-back .bottom-bar .sticker-outline {
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
        .ruths-blend-back .bottom-bar .sticker-outline .label {
            position: absolute;
            top: 1mm;
            left: 1mm;
            font-size: 2.5mm;
            color: #ff0080;
            font-family: -apple-system, sans-serif;
        }
    </style>
    <div class="ruths-blend-back">
        <h1 class="title">{{ $backTitle }}</h1>

        <p class="ingredients">
            <span class="section-heading">Inhaltsstoffe:</span>
            {{ $inhaltsstoffeText }}@if ($allBio) aus kontrolliert biologischem Anbau {{ $brand['oeko_code'] ?? 'DE-ÖKO-039' }}.
            @elseif (! empty($inhaltsstoffeText));@endif
            @if ($isBio && ! $allBio)
                <span class="bio-claim">*aus ökologischer EU-/nicht-EU-Landwirtschaft {{ $brand['oeko_code'] ?? 'DE-ÖKO-039' }}</span>
            @endif
        </p>

        <h3 class="section-heading">Zubereitungshinweise:</h3>
        <div class="prep-row">
            <div class="item">
                <div class="icon">@if ($prepAmountIconSvg){!! $prepAmountIconSvg !!}@endif</div>
                <div class="caption">{!! $renderCaption($prepAmount) !!}</div>
            </div>
            <div class="item">
                <div class="icon">@if ($prepTemperatureIconSvg){!! $prepTemperatureIconSvg !!}@endif</div>
                <div class="caption">{!! $renderCaption($prepTemperature) !!}</div>
            </div>
            <div class="item">
                <div class="icon">@if ($prepTimeIconSvg){!! $prepTimeIconSvg !!}@endif</div>
                <div class="caption">{!! $renderCaption($prepTime) !!}</div>
            </div>
        </div>

        <p class="preparation-body">{{ $preparationBodyText }}</p>

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
