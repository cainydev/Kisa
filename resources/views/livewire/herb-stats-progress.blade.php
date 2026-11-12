<div @if ($running) wire:poll.1s="refreshProgress" @endif class="flex w-64 flex-col gap-1">
    <div class="flex items-center justify-between gap-2 text-sm text-gray-600 dark:text-gray-300">
        <span class="flex items-center gap-1.5">
            <x-filament::loading-indicator class="h-4 w-4" />
            @if ($percentage === null)
                Wartet auf Worker …
            @else
                Berechne {{ $processed }} / {{ $total }}
            @endif
        </span>
        @if ($percentage !== null)
            <span class="font-medium tabular-nums">{{ $percentage }} %</span>
        @endif
    </div>
    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
        <div
            @class([
                'h-full rounded-full bg-primary-500 transition-all duration-700',
                'animate-pulse' => $percentage === null,
            ])
            style="width: {{ $percentage ?? 100 }}%"
        ></div>
    </div>
</div>
