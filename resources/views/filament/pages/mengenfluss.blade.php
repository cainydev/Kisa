@php
    $totals = $this->totals();
@endphp

<x-filament-panels::page>
    {{-- Header: filter + reset + range badge --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            {{ $this->filterAction }}
            {{ $this->clearAction }}

            @if ($this->hasRange())
                <x-filament::badge color="gray" icon="heroicon-m-calendar">
                    {{ $this->rangeLabel() }}
                </x-filament::badge>
            @else
                <span class="text-sm text-gray-500 dark:text-gray-400">Gesamter Zeitraum</span>
            @endif
        </div>
    </div>

    {{-- KPI tiles --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ([
            ['Rohstoffe', number_format($totals['herbs'], 0, ',', '.'), 'heroicon-o-cube-transparent'],
            ['Eingang', $this->kg($totals['delivered']), 'carbon-delivery'],
            ['Verbrauch', $this->kg($totals['used']), 'heroicon-o-inbox'],
            ['Ausschuss', $this->kg($totals['trashed']), 'heroicon-o-trash'],
            ['Bestand', $this->kg($totals['stock']), 'heroicon-o-archive-box'],
        ] as [$label, $value, $icon])
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <div class="flex items-center justify-between">
                    <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $value }}</div>
                    <x-filament::icon :icon="$icon" class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                </div>
                <div class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    {{-- Implausibility banner (clickable filter) --}}
    @if ($totals['implausible'] > 0)
        <button type="button" wire:click="toggleIssues"
            @class([
                'flex w-full items-center gap-3 rounded-xl p-4 text-left transition',
                'bg-danger-50 hover:bg-danger-100 dark:bg-danger-500/10 dark:hover:bg-danger-500/20' => ! $this->onlyIssues,
                'bg-danger-100 ring-2 ring-danger-500 dark:bg-danger-500/20' => $this->onlyIssues,
            ])>
            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-6 w-6 shrink-0 text-danger-500" />
            <div class="flex-1">
                <div class="font-semibold text-danger-700 dark:text-danger-400">
                    {{ $totals['implausible'] }} {{ $totals['implausible'] === 1 ? 'Rohstoff' : 'Rohstoffe' }} mit Mengendifferenz
                </div>
                <div class="text-sm text-danger-600/80 dark:text-danger-400/70">
                    Hier wurde mehr verbraucht/verworfen als im Zeitraum eingegangen ist.
                    {{ $this->onlyIssues ? 'Klicken, um wieder alle anzuzeigen.' : 'Klicken, um nur diese anzuzeigen.' }}
                </div>
            </div>
            <x-filament::icon :icon="$this->onlyIssues ? 'heroicon-m-funnel' : 'heroicon-m-chevron-right'" class="h-5 w-5 text-danger-500" />
        </button>
    @else
        <div class="flex items-center gap-2 rounded-xl bg-success-50 p-4 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
            <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5" />
            Alle Rohstoffe sind mengenmäßig plausibel — kein Verbrauch übersteigt den Eingang.
        </div>
    @endif

    {{-- Native Filament table: Warenstrombilanz je Rohstoff --}}
    {{ $this->table }}
</x-filament-panels::page>
