@php
    $graph = $this->buildGraph();
    $hasResult = $this->hasResult();
    $agg = $hasResult ? $this->getAggregates() : null;
@endphp

<x-filament-panels::page>
    {{-- Header: filter + reset + legend --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            {{ $this->filterAction }}
            {{ $this->clearAction }}

            @if ($this->hasQuery() && ($this->dateFrom || $this->dateTo))
                <x-filament::badge color="gray" icon="heroicon-m-calendar">
                    {{ $this->dateFrom ? \Illuminate\Support\Carbon::parse($this->dateFrom)->format('d.m.Y') : '…' }}
                    –
                    {{ $this->dateTo ? \Illuminate\Support\Carbon::parse($this->dateTo)->format('d.m.Y') : '…' }}
                </x-filament::badge>
            @endif
        </div>

        @if ($hasResult)
            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background: rgb(var(--primary-500))"></span>
                    Ausgewählt
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-danger-500"></span>
                    Auffälligkeit
                </span>
            </div>
        @endif
    </div>

    @if (! $this->hasQuery())
        {{-- Idle state --}}
        <div class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-gray-300 py-20 text-center dark:border-gray-700">
            <x-filament::icon icon="heroicon-o-qr-code" class="h-16 w-16 text-gray-300 dark:text-gray-600" />
            <div>
                <p class="text-lg font-medium text-gray-700 dark:text-gray-200">Warenweg verfolgen</p>
                <p class="mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
                    Charge, Produkt, Variante, Gebinde oder Abfüllung auswählen,
                    um den vollständigen Warenweg von der Lieferung bis zum Produkt anzuzeigen.
                </p>
            </div>
        </div>
    @elseif (! $hasResult)
        {{-- Not found --}}
        <div class="flex flex-col items-center justify-center gap-3 rounded-xl bg-danger-50 py-16 text-center dark:bg-danger-500/10">
            <x-filament::icon icon="heroicon-o-x-circle" class="h-14 w-14 text-danger-500" />
            <p class="text-lg font-semibold text-danger-700 dark:text-danger-400">
                Nichts gefunden.
            </p>
            <p class="max-w-sm text-sm text-danger-600/80 dark:text-danger-400/70">
                Für „{{ $this->activeLabel() }}"
                @if ($this->dateFrom || $this->dateTo) im gewählten Zeitraum @endif
                gibt es keine erfassten Daten. Prüfe die Auswahl oder erweitere den Zeitraum.
            </p>
        </div>
    @else
        {{-- The node id that is the current entity target, if any. Only bag/filling
             entity traces have one; charge/product/variant traces do not, so the
             re-anchor button shows on every eligible node in those modes. --}}
        @php($currentTargetId = in_array($this->type, ['bag', 'filling', 'delivery'], true) && $this->entityId
            ? "{$this->type}:{$this->entityId}"
            : null)

        {{-- Graph + detail modal. The warenwegGraph component is registered
             eagerly via FilamentAsset (see AppServiceProvider), so it is always
             defined before this x-data is evaluated. --}}
        <div
            wire:key="warenweg-{{ md5(json_encode([$this->type, $this->charge, $this->entityId, $this->dateFrom, $this->dateTo])) }}"
            x-data="warenwegGraph({
                nodes: @js($graph['nodes']),
                edges: @js($graph['edges']),
                anchor: @js($graph['anchor']),
                currentTargetId: @js($currentTargetId),
            })"
            x-on:keydown.window.escape="modalOpen ? closeModal() : fit()"
        >
            <div
                wire:ignore
                class="relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-gray-950/40"
                style="height: min(68vh, 660px);"
            >
                {{-- Toolbar --}}
                <div class="absolute right-3 top-3 z-10 flex flex-col gap-1.5">
                    <button type="button" x-on:click="recenter()"
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-gray-600 shadow-sm ring-1 ring-gray-950/5 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-gray-700"
                        title="Auf Auswahl zentrieren">
                        <x-filament::icon icon="heroicon-m-viewfinder-circle" class="h-5 w-5" />
                    </button>
                    <button type="button" x-on:click="fit()"
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-gray-600 shadow-sm ring-1 ring-gray-950/5 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-gray-700"
                        title="Alles anzeigen">
                        <x-filament::icon icon="heroicon-m-arrows-pointing-out" class="h-5 w-5" />
                    </button>
                </div>

                <div class="pointer-events-none absolute bottom-3 left-3 z-10 rounded-lg bg-white/80 px-2.5 py-1 text-xs text-gray-500 backdrop-blur dark:bg-gray-900/70 dark:text-gray-400">
                    Ziehen · Zoomen · Knoten anklicken für Details
                </div>

                <div x-ref="canvas" class="h-full w-full" style="cursor: grab;"></div>
            </div>

            {{-- Detail modal --}}
            <div
                x-show="modalOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-40 flex items-center justify-center bg-gray-950/50 p-4"
                x-on:click.self="closeModal()"
            >
                <div
                    x-show="modalOpen"
                    x-transition
                    class="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-xl dark:bg-gray-900"
                >
                    <template x-if="detailNode">
                        <div>
                            {{-- Modal header --}}
                            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4 dark:border-white/10">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300">
                                        <span x-show="detailNode.type === 'supplier'">
                                            <x-filament::icon icon="heroicon-o-user-group" class="h-6 w-6" />
                                        </span>
                                        <span x-show="detailNode.type === 'delivery'">
                                            <x-filament::icon icon="carbon-delivery" class="h-6 w-6" />
                                        </span>
                                        <span x-show="detailNode.type === 'herb'">
                                            <x-filament::icon icon="heroicon-o-cube-transparent" class="h-6 w-6" />
                                        </span>
                                        <span x-show="detailNode.type === 'bag'">
                                            <x-filament::icon icon="heroicon-o-shopping-bag" class="h-6 w-6" />
                                        </span>
                                        <span x-show="detailNode.type === 'filling'">
                                            <x-filament::icon icon="heroicon-o-inbox" class="h-6 w-6" />
                                        </span>
                                        <span x-show="detailNode.type === 'product'">
                                            <x-filament::icon icon="heroicon-o-cube" class="h-6 w-6" />
                                        </span>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white"
                                            x-text="detailNode.detail?.title ?? detailNode.label"></h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400"
                                            x-text="detailNode.sublabel"></p>
                                    </div>
                                </div>
                                <button type="button" x-on:click="closeModal()"
                                    class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10">
                                    <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                                </button>
                            </div>

                            {{-- Loading state while the detail is fetched --}}
                            <div x-show="detailLoading" class="flex items-center justify-center gap-2 px-6 py-10 text-sm text-gray-500 dark:text-gray-400">
                                <x-filament::loading-indicator class="h-5 w-5" />
                                Details werden geladen …
                            </div>

                            <div class="space-y-5 px-6 py-5" x-show="! detailLoading">
                                {{-- Release banner (delivery) --}}
                                <template x-if="detailNode.detail?.released !== undefined">
                                    <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium"
                                        :class="detailNode.detail.released
                                            ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400'
                                            : 'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400'">
                                        <span x-show="detailNode.detail.released">
                                            <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5" />
                                        </span>
                                        <span x-show="! detailNode.detail.released">
                                            <x-filament::icon icon="heroicon-m-x-circle" class="h-5 w-5" />
                                        </span>
                                        <span x-text="detailNode.detail.released ? 'Ware freigegeben' : 'Ware nicht freigegeben'"></span>
                                    </div>
                                </template>

                                {{-- Key/value rows --}}
                                <template x-if="detailNode.detail?.rows?.length">
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                                        <template x-for="row in detailNode.detail.rows" :key="row.label">
                                            <div>
                                                <dt class="text-xs text-gray-500 dark:text-gray-400" x-text="row.label"></dt>
                                                <dd class="font-medium"
                                                    :class="row.highlight ? 'text-primary-600 dark:text-primary-400' : 'text-gray-950 dark:text-white'"
                                                    x-text="row.value ?? '—'"></dd>
                                            </div>
                                        </template>
                                    </dl>
                                </template>

                                {{-- Consumption bar (bag): verworfen / verbraucht / übrig --}}
                                <template x-if="detailNode.detail?.amount">
                                    <div x-data="{
                                        a: detailNode.detail.amount,
                                        pct(v) { return this.a.total > 0 ? (v / this.a.total) * 100 : 0; },
                                    }">
                                        <div class="mb-2 flex items-baseline justify-between">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Füllstand</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-semibold text-success-600 dark:text-success-400"
                                                    x-text="Math.round(pct(a.free)) + '%'"></span>
                                                übrig
                                            </p>
                                        </div>

                                        <div class="flex h-6 w-full overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
                                            <div class="bg-danger-500 transition-all" :style="`width: ${pct(a.trashed)}%`"
                                                title="Ausschuss"></div>
                                            <div class="bg-warning-500 transition-all" :style="`width: ${pct(a.used)}%`"
                                                title="Verbraucht"></div>
                                            <div class="bg-success-500 transition-all" :style="`width: ${pct(a.free)}%`"
                                                title="Übrig"></div>
                                        </div>

                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                            <span class="flex items-center gap-1.5" x-show="a.trashed > 0">
                                                <span class="h-2.5 w-2.5 rounded-sm bg-danger-500"></span>
                                                <span class="text-gray-600 dark:text-gray-300">Ausschuss</span>
                                                <span class="font-medium text-gray-950 dark:text-white" x-text="a.trashed + ' g'"></span>
                                            </span>
                                            <span class="flex items-center gap-1.5" x-show="a.used > 0">
                                                <span class="h-2.5 w-2.5 rounded-sm bg-warning-500"></span>
                                                <span class="text-gray-600 dark:text-gray-300">Verbraucht</span>
                                                <span class="font-medium text-gray-950 dark:text-white" x-text="a.used + ' g'"></span>
                                            </span>
                                            <span class="flex items-center gap-1.5">
                                                <span class="h-2.5 w-2.5 rounded-sm bg-success-500"></span>
                                                <span class="text-gray-600 dark:text-gray-300">Übrig</span>
                                                <span class="font-medium text-gray-950 dark:text-white" x-text="a.free + ' g'"></span>
                                            </span>
                                            <span class="ml-auto flex items-center gap-1.5 text-gray-400 dark:text-gray-500">
                                                <span>gesamt</span>
                                                <span x-text="a.total + ' g'"></span>
                                            </span>
                                        </div>
                                    </div>
                                </template>

                                {{-- Wareneingangskontrolle checklist (delivery) --}}
                                <template x-if="detailNode.detail?.checks?.length">
                                    <div>
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Wareneingangskontrolle</p>
                                        <ul class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                            <template x-for="check in detailNode.detail.checks" :key="check.label">
                                                <li class="flex items-center gap-2 text-sm">
                                                    <span x-show="check.ok" class="text-success-500">
                                                        <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5" />
                                                    </span>
                                                    <span x-show="! check.ok" class="text-danger-500">
                                                        <x-filament::icon icon="heroicon-m-x-circle" class="h-5 w-5" />
                                                    </span>
                                                    <span :class="check.ok ? 'text-gray-700 dark:text-gray-300' : 'font-medium text-danger-700 dark:text-danger-400'"
                                                        x-text="check.label"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>

                                {{-- Documents (delivery) --}}
                                <template x-if="detailNode.detail?.documents?.length">
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="doc in detailNode.detail.documents" :key="doc.label">
                                            <span>
                                                <a x-show="doc.present" :href="doc.url" target="_blank"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-success-50 px-3 py-1.5 text-sm font-medium text-success-700 hover:bg-success-100 dark:bg-success-500/10 dark:text-success-300">
                                                    <x-filament::icon icon="heroicon-m-document-text" class="h-4 w-4" />
                                                    <span x-text="doc.label"></span>
                                                </a>
                                                <span x-show="!doc.present"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-danger-50 px-3 py-1.5 text-sm font-medium text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">
                                                    <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
                                                    <span x-text="doc.label + ' fehlt'"></span>
                                                </span>
                                            </span>
                                        </template>
                                    </div>
                                </template>

                                {{-- Recipe (product / variant / herb): herbs with their share --}}
                                <template x-if="detailNode.detail?.recipe?.length">
                                    <div>
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Rezeptur</p>
                                        <div class="space-y-1.5">
                                            <template x-for="ing in detailNode.detail.recipe" :key="ing.herb">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-32 shrink-0 truncate text-sm text-gray-700 dark:text-gray-300" x-text="ing.herb"></div>
                                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                                        <div class="h-full rounded-full bg-primary-500" :style="`width: ${ing.percentage}%`"></div>
                                                    </div>
                                                    <div class="w-12 shrink-0 text-right text-sm tabular-nums font-medium text-gray-950 dark:text-white"
                                                        x-text="ing.percentage.toLocaleString('de-DE') + '%'"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                {{-- Link to record --}}
                                <template x-if="detailNode.detail?.url">
                                    <a :href="detailNode.detail.url"
                                        class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                                        <span x-text="detailNode.detail.urlLabel ?? 'Öffnen'"></span>
                                        <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-4 w-4" />
                                    </a>
                                </template>
                            </div>

                            {{-- Footer: re-anchor the trace onto this node (bag/filling only) --}}
                            <div class="border-t border-gray-100 px-6 py-4 dark:border-white/10"
                                x-show="['bag', 'filling', 'delivery'].includes(detailNode.type) && detailNode.id !== currentTargetId">
                                <button type="button"
                                    x-on:click="closeModal(); $wire.traceNode(detailNode.type, Number(detailNode.id.split(':')[1]))"
                                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                                    <x-filament::icon icon="heroicon-m-arrows-pointing-in" class="h-5 w-5" />
                                    <span x-text="'Von hier aus verfolgen'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Aggregations / summary of shown data --}}
        <x-filament::section>
            <x-slot name="heading">Übersicht der angezeigten Daten</x-slot>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    ['Lieferanten', $agg['suppliers'], 'heroicon-o-user-group'],
                    ['Lieferungen', $agg['deliveries'], 'carbon-delivery'],
                    ['Gebinde', $agg['bags'], 'heroicon-o-shopping-bag'],
                    ['Abfüllungen', $agg['fillings'], 'heroicon-o-inbox'],
                    ['Stück abgefüllt', $agg['unitsOut'], 'heroicon-o-cube'],
                ] as [$label, $value, $icon])
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <div class="flex items-center justify-between">
                            <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($value, 0, ',', '.') }}</div>
                            <x-filament::icon :icon="$icon" class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                        </div>
                        <div class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    </div>
                @endforeach
            </div>

            @if ($agg['flaggedNodes'] > 0)
                <div class="mt-4 rounded-xl bg-danger-50 p-4 dark:bg-danger-500/10">
                    <div class="flex items-center gap-2 font-semibold text-danger-700 dark:text-danger-400">
                        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-5 w-5" />
                        {{ $agg['flaggedNodes'] }} {{ $agg['flaggedNodes'] === 1 ? 'auffälliger Knoten' : 'auffällige Knoten' }} im angezeigten Warenweg
                    </div>
                    <p class="mt-0.5 text-xs text-danger-600/80 dark:text-danger-400/70">
                        Rot markierte Knoten anklicken, um Details zu sehen.
                    </p>
                    <ul class="mt-3 space-y-1.5 text-sm">
                        @foreach ($agg['gapGroups'] as $group)
                            <li class="flex items-center justify-between gap-3 text-danger-700 dark:text-danger-400/90">
                                <span>{{ $group['reason'] }}</span>
                                <span class="shrink-0 rounded-full bg-danger-100 px-2 py-0.5 text-xs font-semibold text-danger-700 dark:bg-danger-500/20 dark:text-danger-300">
                                    {{ $group['count'] }}×
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="mt-4 flex items-center gap-2 rounded-xl bg-success-50 p-4 text-sm font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                    <x-filament::icon icon="heroicon-m-check-circle" class="h-5 w-5" />
                    Keine Auffälligkeiten im angezeigten Warenweg.
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
