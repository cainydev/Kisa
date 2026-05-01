@php
    use App\Labels\BioMode;
    use App\Labels\IngredientList;

    $subtitleColor = $subtitleColor ?? '#6f7070';
    $textColor = $textColor ?? '#1c1d1c';
    $brand = config('labels.brand');
    $bioMode = BioMode::tryFrom((string) ($bioMode ?? '')) ?? BioMode::Bio;

    $list = IngredientList::build($entity ?? null, $bioMode);
    $inhaltsstoffeText = (! empty($inhaltsstoffe)) ? $inhaltsstoffe : $list->text;
    // anyBio drives the asterisk-and-footnote pattern. allBio swaps it for a
    // single closing sentence inline. bioSealsAllowed gates the BIO/EU-leaf
    // seals (≤ 5 % non-bio share, EU 95/5 rule).
    $isBio = $list->anyBio;
    $allBio = $list->allBio || ($bioMode === BioMode::Bio && $list->text !== '');
    $bioSealsAllowed = $isBio && $list->nonBioPercent <= 5.0;

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

    // Tokens supported in any preparationBody value (including overrides):
    //   {prepAmount}    → e.g. "1-2"
    //   {prepTime}      → e.g. "5-8 Min."
    //   {prepTimeLong}  → "5-8 Min." → "5-8 Minuten"
    $expandPrepTime = function (?string $t): string {
        $t = trim((string) $t);
        if ($t === '') {
            return '';
        }
        // "5 Min." / "5 Min" / "5 min." → "5 Minuten" (consume the period too).
        return preg_replace('/\bMin\b\.?/iu', 'Minuten', $t);
    };
    $prepAmountVal = trim((string) ($prepAmount ?? ''));
    $prepAmountForBody = preg_replace('/\s*TL\s*$/iu', '', $prepAmountVal);
    $prepTimeLong = $expandPrepTime($prepTime ?? '');
    $tokens = [
        '{prepAmount}' => $prepAmountForBody !== '' ? $prepAmountForBody : '1-2',
        '{prepTime}' => $prepTime ?? '5-8 Min.',
        '{prepTimeLong}' => $prepTimeLong !== '' ? $prepTimeLong : '5-8 Minuten',
    ];
    // Strip stored soft hyphens so {token} placeholders match, run substitution,
    // then re-hyphenate the final text so the output is line-break-friendly.
    $stripSoftHyphens = fn (?string $s) => str_replace("\u{00AD}", '', (string) $s);
    $preparationBodyText = (! empty($preparationBody))
        ? app(\App\Labels\Hyphenator::class)->hyphenate(strtr($stripSoftHyphens($preparationBody), $tokens))
        : '';

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
        .herb-blend-back {
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
            justify-content: space-between;
        }
        .herb-blend-back .title {
            font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
            font-size: 6mm;
            line-height: 1;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: {{ $accentColor }};
            margin: 0;
        }
        .herb-blend-back .ingredients {
            margin: 0;
            font-family: 'herb-body', -apple-system, sans-serif;
            font-size: 2.82mm;
            line-height: 1.125;
            hyphens: auto;
            -webkit-hyphens: auto;
            hyphenate-limit-chars: 6 3 3;
            overflow-wrap: break-word;
        }
        .herb-blend-back .ingredients .bio-claim {
            display: block;
            margin-top: 1.5mm;
            font-family: 'herb-body', -apple-system, sans-serif;
            font-size: 2.82mm;
            line-height: 1.125;
        }
        .herb-blend-back .latin { font-family: 'herb-italic', 'herb-body', -apple-system, sans-serif; font-style: italic; }
        /* Shared heading style: "Inhaltsstoffe:", "Zubereitungshinweise:",
           "Sicherheitshinweis:" — same typography regardless of position. */
        .herb-blend-back .section-heading {
            font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
            font-size: 3.5mm;
            line-height: 1;
        }
        .herb-blend-back h3.section-heading {
            margin: 0;
        }
        /* Three columns laid out left / center / right with a shared two-row
           subgrid so icons in row 1 stay aligned across items and captions in
           row 2 do too — even when one caption wraps to a second line. Within
           each item, both icon and caption are independently centered. */
        .herb-blend-back .prep-row {
            display: grid;
            grid-template-columns: auto auto auto;
            grid-template-rows: auto auto;
            justify-content: space-between;
            margin: 0;
        }
        .herb-blend-back .prep-row .item {
            display: grid;
            grid-template-rows: subgrid;
            grid-row: span 2;
            justify-items: center;
        }
        .herb-blend-back .prep-row .item:nth-child(1) { justify-self: start; }
        .herb-blend-back .prep-row .item:nth-child(2) { justify-self: center; }
        .herb-blend-back .prep-row .item:nth-child(3) { justify-self: end; }
        .herb-blend-back .prep-row .icon {
            display: flex;
            align-items: end;
            justify-content: center;
            height: 25mm;
            margin-bottom: 2.5mm;
        }
        .herb-blend-back .prep-row .icon img,
        .herb-blend-back .prep-row .icon svg {
            width: auto;
            height: 25mm;
            object-fit: contain;
            display: block;
        }
        .herb-blend-back .prep-row .caption {
            align-self: start;
            font-family: 'herb-accent', 'herb-body', -apple-system, sans-serif;
            font-size: 3.88mm;
            line-height: 1;
            text-align: center;
            color: {{ $textColor }};
        }
        .herb-blend-back .preparation-body {
            margin: 0;
            line-height: 1.1;
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
            hyphenate-limit-chars: 6 3 3;
        }
        .herb-blend-back .safety-hint {
            margin: 0;
            line-height: 1.1;
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
            hyphenate-limit-chars: 6 3 3;
        }
        /* Seals row: full-width strip with the fill-hint on the left and the
           seals group on the right (justify-between). */
        .herb-blend-back .seals-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 0;
        }
        .herb-blend-back .seals-row .fill-hint {
            font-size: 2.82mm;
            line-height: 1.2;
            max-width: 36mm;
        }
        /* Seal group: flex row, items aligned to the start (top) so the
           EU-seal column can hang the caption below without affecting the
           other seals' vertical placement. */
        .herb-blend-back .seals-row .seals {
            display: flex;
            justify-content: flex-end;
            align-items: flex-start;
            gap: 2mm;
        }
        .herb-blend-back .seals-row .seals .gruen-punkt,
        .herb-blend-back .seals-row .seals .bio-seal {
            height: 13mm;
            width: auto;
            object-fit: contain;
            display: block;
        }
        /* Bottom flex child holding the placement bar plus the sticker overlay
           that physically covers it. The sticker is sized to its real-world
           extent so this child's height is the sticker's height — that
           reserves exactly the right amount of space at the bottom of the
           page when justify-content distributes the rest. */
        .herb-blend-back .footer {
            position: relative;
            width: 95mm;
            height: 48mm;
            margin: 0 auto;
            pointer-events: none;
        }
        .herb-blend-back .footer .bar {
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 50.20mm;
            height: 0.50mm;
            background: #d8d8d8;
            border-radius: 0.25mm;
        }
        .herb-blend-back .footer .outline {
            position: absolute;
            inset: 0;
            background: rgba(255, 0, 128, 0.12);
            border: 0.3mm dashed #ff0080;
            z-index: 99;
        }
        .herb-blend-back .footer .footer-label {
            position: absolute;
            top: 1mm;
            left: 1mm;
            font-size: 2.5mm;
            color: #ff0080;
            font-family: -apple-system, sans-serif;
        }
    </style>
    <div class="herb-blend-back">
        <h1 class="title">{{ $title }}</h1>

        <p class="ingredients">
            <span class="section-heading">Inhaltsstoffe:</span>
            {{ $inhaltsstoffeText }}@if ($allBio) aus kontrolliert biologischem Anbau {{ $brand['oeko_code'] ?? 'DE-ÖKO-039' }}.
            @elseif (! empty($inhaltsstoffeText));@endif
            @if ($isBio && ! $allBio)
                <span class="bio-claim">*aus ökologischer EU-/nicht-EU-Landwirtschaft {{ $brand['oeko_code'] ?? 'DE-ÖKO-039' }}</span>
            @endif
        </p>

        <div class="prep-section">
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
        </div>

        <p class="preparation-body">{{ $preparationBodyText }}</p>

        <p class="safety-hint"><span class="section-heading">Sicherheitshinweis:</span> {{ $safetyHint }}</p>

        <div class="seals-row">
            <div class="fill-hint">{{ $fillVolumeHint }}</div>
            <div class="seals">
                @if ($gruenPunktSrc) <img src="{{ $gruenPunktSrc }}" class="gruen-punkt" alt=""> @endif
                @if ($bioSealSrc) <img src="{{ $bioSealSrc }}" class="bio-seal" alt=""> @endif
                <x-eu-bio-seal
                    :src="$euBioLeafSrc"
                    :code="$brand['oeko_code'] ?? 'DE-ÖKO-039'"
                    :origin="$brand['oeko_origin'] ?? 'EU-/Nicht-EU-Landwirtschaft'"
                    captionLayout="stacked"
                    :color="$textColor"
                />
            </div>
        </div>

        <div class="footer">
            <div class="bar"></div>
            @if (! empty($showStickerOutline))
                <div class="outline">
                    <span class="footer-label">Aufkleber 95×48 mm</span>
                </div>
            @endif
        </div>
    </div>
</x-label-page>
