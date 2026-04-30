@php
    $subtitleColor = $subtitleColor ?? '#6f7070';
    $textColor = $textColor ?? '#1c1d1c';

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

    $bioMode = \App\Labels\BioMode::tryFrom((string) ($bioMode ?? '')) ?? \App\Labels\BioMode::Bio;
    // Walk the recipe once to derive both `anyBio` and the non-bio percentage
    // sum. Bio seals are only legal when the product qualifies as bio (≤ 5 %
    // non-bio share, EU 95/5 rule).
    [$anyBio, $nonBioPercent] = (function () use ($bioMode, $entity) {
        if ($bioMode === \App\Labels\BioMode::Bio) {
            return [true, 0.0];
        }
        if ($bioMode === \App\Labels\BioMode::None || ! $entity) {
            return [false, 0.0];
        }
        $any = false;
        $nonBio = 0.0;
        foreach ($entity->herbs as $herb) {
            $pct = (float) ($herb->pivot->percentage ?? 0);
            if ($bioMode->herbIsBio($herb)) {
                $any = true;
            } else {
                $nonBio += $pct;
            }
        }
        return [$any, $nonBio];
    })();
    $isBio = $anyBio || $bioMode === \App\Labels\BioMode::Bio;
    $bioSealsAllowed = $isBio && $nonBioPercent <= 5.0;
    $backgroundSrc = $imgSrc($background ?? null);
    $brandLogoSrc = $imgSrc($brandLogo ?? null);
    $bioSealSrc = $bioSealsAllowed ? $imgSrc($bioSeal ?? null) : null;

    $titleFontFace = $fontFace($titleFont ?? null, 'herb-title');
    $bodyFontFace = $fontFace($bodyFont ?? null, 'herb-body');
    $subtitleFontFace = $fontFace($subtitleFont ?? null, 'herb-subtitle');
@endphp
<x-label-page :width="$width" :height="$height" :bleed="$bleed" :marks="$marks" :slug="$slug ?? null">
    <style>
        {!! $titleFontFace !!}
        {!! $bodyFontFace !!}
        {!! $subtitleFontFace !!}
        .herb-blend-front {
            position: relative;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            padding: 7mm 7mm 7mm 7mm;
            color: {{ $textColor }};
            font-family: 'herb-body', -apple-system, sans-serif;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        /* Background image: full-bleed, covering the entire front page. */
        .herb-blend-front .bg-wrap {
            position: absolute;
            inset: 0;
            z-index: 1;
            overflow: hidden;
        }
        .herb-blend-front .bg-wrap img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
        }
        .herb-blend-front .logo-wrap {
            position: relative;
            z-index: 3;
            display: flex;
            justify-content: center;
            margin-bottom: 2mm;
        }
        .herb-blend-front .logo-wrap img {
            max-width: 35.59mm;
            max-height: 33mm;
            object-fit: contain;
        }
        .herb-blend-front .bio-seal {
            position: absolute;
            top: 69.40mm;
            left: 9.98mm;
            z-index: 4;
        }
        .herb-blend-front .bio-seal img {
            width: 19.23mm;
            height: 15.95mm;
        }
        .herb-blend-front .heading {
            position: absolute;
            left: 9.88mm;
            right: 9.88mm;
            bottom: 8.70mm;
            z-index: 3;
            color: #fff;
        }
        .herb-blend-front .title {
            font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
            font-size: 9.10mm;
            line-height: 1;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin: 0;
        }
        .herb-blend-front .subtitle {
            font-family: 'herb-subtitle', 'herb-body', -apple-system, sans-serif;
            font-size: 4.73mm;
            line-height: 1.1;
            letter-spacing: 0.057em;
            margin: 1.5mm 0 0 0;
        }
    </style>
    <div class="herb-blend-front">
        @if ($backgroundSrc)
            <div class="bg-wrap"><img src="{{ $backgroundSrc }}" alt=""></div>
        @endif

        <div class="logo-wrap">
            @if ($brandLogoSrc)
                <img src="{{ $brandLogoSrc }}" alt="">
            @endif
        </div>

        <div class="heading">
            <h1 class="title">{{ $title }}</h1>
            @if (!empty($subtitle))
                <p class="subtitle">{{ $subtitle }}</p>
            @endif
        </div>

        @if ($bioSealSrc)
            <div class="bio-seal"><img src="{{ $bioSealSrc }}" alt=""></div>
        @endif
    </div>
</x-label-page>
