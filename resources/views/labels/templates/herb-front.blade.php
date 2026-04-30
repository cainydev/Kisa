@php
    $headingColor = $headingColor ?? '#d8dc8e';
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
    $isBio = (function () use ($bioMode, $entity) {
        if ($bioMode === \App\Labels\BioMode::Bio) {
            return true;
        }
        if ($bioMode === \App\Labels\BioMode::None || ! $entity) {
            return false;
        }
        foreach ($entity->herbs ?? [] as $herb) {
            if ($bioMode->herbIsBio($herb)) {
                return true;
            }
        }

        return false;
    })();

    $artworkSrc = $imgSrc($artwork ?? null);
    $brandLogoSrc = $imgSrc($brandLogo ?? null);
    $bioSealSrc = $isBio ? $imgSrc($bioSeal ?? null) : null;

    $artworkRotate = (float) ($artworkRotate ?? 0);
    $artworkOffsetX = (float) ($artworkOffsetX ?? 0);
    $artworkOffsetY = (float) ($artworkOffsetY ?? 0);
    $artworkScale = (float) ($artworkScale ?? 1);
    $artworkScaleX = ($artworkMirror ?? false) ? -$artworkScale : $artworkScale;

    $titleFontFace = $fontFace($titleFont ?? null, 'herb-title');
    $bodyFontFace = $fontFace($bodyFont ?? null, 'herb-body');
    $subtitleFontFace = $fontFace($subtitleFont ?? null, 'herb-subtitle');
@endphp
<x-label-page :width="$width" :height="$height" :bleed="$bleed" :marks="$marks" :slug="$slug ?? null">
    <style>
        {!! $titleFontFace !!}
        {!! $bodyFontFace !!}
        {!! $subtitleFontFace !!}
        .herb-front {
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
        .herb-front .logo-wrap {
            position: relative;
            z-index: 3;
            display: flex;
            justify-content: center;
            margin-bottom: 2mm;
        }
        .herb-front .logo-wrap img {
            max-width: 35.59mm;
            max-height: 33mm;
            object-fit: contain;
        }
        .herb-front .art-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .herb-front .art-wrap img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transform: translate({{ $artworkOffsetX }}mm, {{ $artworkOffsetY }}mm) rotate({{ $artworkRotate }}deg) scale({{ $artworkScaleX }}, {{ $artworkScale }});
            transform-origin: center center;
        }
        .herb-front .bio-seal {
            position: absolute;
            top: 90mm;
            left: 7mm;
            z-index: 4;
        }
        .herb-front .bio-seal img {
            width: 18mm;
            height: auto;
        }
        .herb-front .heading {
            position: relative;
            z-index: 3;
            margin-top: auto;
            text-align: left;
        }
        .herb-front .title {
            font-family: 'herb-title', 'herb-body', -apple-system, sans-serif;
            font-size: 9.9mm;
            line-height: 0.95;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: {{ $headingColor }};
            margin: 0 0 0.5mm 0;
            -webkit-hyphens: auto;
            hyphens: auto;
            overflow-wrap: break-word;
        }
        .herb-front .subtitle {
            font-family: 'herb-subtitle', 'herb-body', -apple-system, sans-serif;
            font-size: 4.9mm;
            letter-spacing: 0.057em;
            line-height: 1.143;
            color: {{ $textColor }};
            margin: 0;
        }
    </style>
    <div class="herb-front">
        <div class="logo-wrap">
            @if ($brandLogoSrc)
                <img src="{{ $brandLogoSrc }}" alt="">
            @endif
        </div>

        @if ($bioSealSrc)
            <div class="bio-seal"><img src="{{ $bioSealSrc }}" alt=""></div>
        @endif

        <div class="art-wrap">
            @if ($artworkSrc)
                <img src="{{ $artworkSrc }}" alt="">
            @endif
        </div>

        <div class="heading">
            <h1 class="title">{{ $frontTitle }}</h1>
            @if (!empty($subtitle))
                <p class="subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
</x-label-page>
