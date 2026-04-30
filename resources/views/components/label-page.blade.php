@props([
    'width' => 70,
    'height' => 100,
    'bleed' => 0,
    'marks' => false,
    'slug' => null,
])
@php
    // Crop-mark geometry matches the InDesign 2020 print preset:
    //   - 3 mm bleed (gap between trim and where marks begin)
    //   - 5 mm crop-mark stub length, drawn outward from the bleed edge
    //   - 0.25 mm stroke
    // When marks are on, the sheet must include extra margin beyond the bleed so
    // the marks have somewhere to live. We pad an additional $markLen so marks
    // sit fully inside the printable area.
    $markLen = 5; // mm
    $markStroke = 0.25; // mm
    $markGap = $bleed; // gap between trim and mark start = bleed depth

    // The total margin from sheet edge to trim is the sum of the bleed plus the
    // crop-mark area when marks are enabled. Without marks, just bleed.
    $marginToTrim = $marks ? $bleed + $markLen : $bleed;
    $pageW = $width + 2 * $marginToTrim;
    $pageH = $height + 2 * $marginToTrim;
@endphp
<style>
    @page { size: {{ $pageW }}mm {{ $pageH }}mm; margin: 0; }
    html, body { margin: 0; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { font-family: -apple-system, "Helvetica Neue", Helvetica, Arial, sans-serif; }
    .lp-sheet {
        position: relative;
        width: {{ $pageW }}mm;
        height: {{ $pageH }}mm;
        page-break-after: always;
        overflow: hidden;
    }
    .lp-page {
        position: absolute;
        top: {{ $marginToTrim }}mm;
        left: {{ $marginToTrim }}mm;
        width: {{ $width }}mm;
        height: {{ $height }}mm;
        overflow: hidden;
    }
    .lp-mark {
        position: absolute;
        background: black;
    }
    .lp-slug {
        position: absolute;
        bottom: 1mm;
        left: {{ $marginToTrim }}mm;
        right: {{ $marginToTrim }}mm;
        text-align: center;
        font-family: -apple-system, "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 2mm;
        color: #999;
    }
</style>
<section class="lp-sheet" lang="de">
    <div class="lp-page">{{ $slot }}</div>
    @if ($marks && $bleed > 0)
        @php
            // Trim corners (in sheet coordinates):
            //   left   = $marginToTrim
            //   right  = $marginToTrim + $width
            //   top    = $marginToTrim
            //   bottom = $marginToTrim + $height
            // Marks start $markGap (= bleed) outward from each trim edge and
            // extend an additional $markLen further outward.
            $trimL = $marginToTrim;
            $trimR = $marginToTrim + $width;
            $trimT = $marginToTrim;
            $trimB = $marginToTrim + $height;
            // Offset from sheet edge to where the outer end of each mark sits:
            //   outerStart = sheetEdge → markEnd at sheetEdge + 0
            //   innerEnd   = trim - bleed
            // So vertical-line marks sit at fixed x = trimL or trimR, y from 0
            // to (trimT - bleed).
            $markEndOuter = $marginToTrim - $markGap - $markLen; // = 0 when marks on
            $markEndInner = $marginToTrim - $markGap;
        @endphp
        {{-- Top-left: vertical stub above trim L, horizontal stub left of trim T --}}
        <div class="lp-mark" style="top:{{ $markEndOuter }}mm;left:{{ $trimL }}mm;width:{{ $markStroke }}mm;height:{{ $markLen }}mm;"></div>
        <div class="lp-mark" style="top:{{ $trimT }}mm;left:{{ $markEndOuter }}mm;width:{{ $markLen }}mm;height:{{ $markStroke }}mm;"></div>
        {{-- Top-right --}}
        <div class="lp-mark" style="top:{{ $markEndOuter }}mm;left:{{ $trimR }}mm;width:{{ $markStroke }}mm;height:{{ $markLen }}mm;"></div>
        <div class="lp-mark" style="top:{{ $trimT }}mm;left:{{ $trimR + $markGap }}mm;width:{{ $markLen }}mm;height:{{ $markStroke }}mm;"></div>
        {{-- Bottom-left --}}
        <div class="lp-mark" style="top:{{ $trimB + $markGap }}mm;left:{{ $trimL }}mm;width:{{ $markStroke }}mm;height:{{ $markLen }}mm;"></div>
        <div class="lp-mark" style="top:{{ $trimB }}mm;left:{{ $markEndOuter }}mm;width:{{ $markLen }}mm;height:{{ $markStroke }}mm;"></div>
        {{-- Bottom-right --}}
        <div class="lp-mark" style="top:{{ $trimB + $markGap }}mm;left:{{ $trimR }}mm;width:{{ $markStroke }}mm;height:{{ $markLen }}mm;"></div>
        <div class="lp-mark" style="top:{{ $trimB }}mm;left:{{ $trimR + $markGap }}mm;width:{{ $markLen }}mm;height:{{ $markStroke }}mm;"></div>

        @if (!empty($slug))
            <div class="lp-slug">{{ $slug }}</div>
        @endif
    @endif
</section>
