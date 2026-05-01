@props([
    /** Base64 data URI of the EU bio leaf image. */
    'src',
    /** ÖKO code text shown in the caption (e.g. "DE-ÖKO-039"). */
    'code',
    /** Origin text shown in the caption (e.g. "EU-/Nicht-EU-Landwirtschaft"). */
    'origin',
    /** "inline" puts code · origin on one line. "stacked" breaks them onto two lines. */
    'captionLayout' => 'inline',
    /** Image height in mm. */
    'height' => 13,
    /** Caption font-size in mm. */
    'captionSize' => 1.41,
    /** Vertical gap between leaf and caption in mm. */
    'captionGap' => 1,
    /** Caption text colour. */
    'color' => '#1c1d1c',
])
@php
    if (! $src) {
        return;
    }
@endphp
<div class="lp-eu-seal">
    <img src="{{ $src }}" alt="">
    <div class="lp-eu-seal-cap">
        @if ($captionLayout === 'stacked')
            {{ $code }}<br>{{ $origin }}
        @else
            {{ $code }} · {{ $origin }}
        @endif
    </div>
</div>
<style>
    .lp-eu-seal {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        line-height: 0;
    }
    .lp-eu-seal img {
        height: {{ $height }}mm;
        width: auto;
        object-fit: contain;
        display: block;
    }
    .lp-eu-seal-cap {
        margin-top: {{ $captionGap }}mm;
        font-size: {{ $captionSize }}mm;
        line-height: 1.1;
        text-align: left;
        color: {{ $color }};
    }
</style>
