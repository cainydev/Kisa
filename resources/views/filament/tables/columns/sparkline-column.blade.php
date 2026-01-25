<div class="h-9 px-2 grow">
    @php
        $data = $getState(); // Get raw data to find Max
        $points = $getPoints();
        $interactivePoints = $getInteractivePoints();
        $id = 'spark-' . md5($points . uniqid());

        $threshold = $getThreshold($getRecord()) ?? 0;
        $max = !empty($data) ? max($data) : 0;

        // Calculate where the "Orange" stop should be (0% = Top, 100% = Bottom)
        // If Max is 1000 and Threshold is 200, percentage is 20%.
        // In SVG Y-coords, 0 is top. So we want the stop at (100% - 20%) = 80%.
        if ($max > 0) {
            $percentage = 100 - (($threshold / $max) * 100);
            // Clamp between 5% and 95% so we always see some color transition
            $stopPosition = max(5, min(95, $percentage));
        } else {
            $stopPosition = 100; // All Red if max is 0
        }
    @endphp

    @if($points)
        <svg viewBox="0 0 100 30" class="w-full h-full overflow-visible" preserveAspectRatio="none">
            <defs>
                {{-- DYNAMIC GRADIENT --}}
                {{-- x1/x2=0 means Vertical Gradient --}}
                {{-- y2="30" matches viewbox height to ensure pixels map correctly --}}
                <linearGradient id="stroke-{{ $id }}" x1="0" x2="0" y1="0" y2="30" gradientUnits="userSpaceOnUse">
                    {{-- 1. Top to Threshold: Green --}}
                    <stop offset="0%" stop-color="#10b981"/> {{-- Emerald-500 --}}

                    {{-- 2. At Threshold Line: Turn Orange --}}
                    <stop offset="{{ $stopPosition }}%" stop-color="#f59e0b"/> {{-- Amber-500 --}}

                    {{-- 3. Below Threshold: Turn Red --}}
                    <stop offset="100%" stop-color="#ef4444"/> {{-- Red-500 --}}
                </linearGradient>

                {{-- Fill Gradient (Subtle opacity version) --}}
                <linearGradient id="fill-{{ $id }}" x1="0" x2="0" y1="0" y2="30" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.2"/>
                    <stop offset="100%" stop-color="#fff" stop-opacity="0.0"/>
                </linearGradient>
            </defs>

            {{-- Area Fill --}}
            <polygon points="{{ $points }} 100,30 0,30" fill="url(#fill-{{ $id }})"/>

            {{-- Line Stroke --}}
            <polyline
                points="{{ $points }}"
                fill="none"
                stroke="url(#stroke-{{ $id }})"
                stroke-width="2"
                vector-effect="non-scaling-stroke"
                stroke-linecap="round"
                stroke-linejoin="round"
            />

            {{-- Interaction Layer --}}
            <g class="interactive-layer">
                @foreach($interactivePoints as $point)
                    <rect
                        x="{{ $point['x'] }}"
                        y="0"
                        width="{{ $point['width'] }}"
                        height="30"
                        fill="transparent"
                        class="cursor-crosshair hover:fill-gray-500/10 transition-colors"
                        x-tooltip="{ content: '{{ $point['value'] }}', theme: $store.theme }"
                    />
                @endforeach
            </g>
        </svg>
    @else
        <div class="h-full w-full flex items-center justify-center">
            <span class="text-xs text-gray-300">-</span>
        </div>
    @endif
</div>
