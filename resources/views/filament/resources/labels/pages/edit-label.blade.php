<x-filament-panels::page>
    @php
        $form = $this->form;
        $topBar = $form->getComponent('topBar');
        $parameters = $form->getComponent('parameters');
    @endphp

    {{-- Save status chip (top-right, fixed to viewport) --}}
    <div class="fixed top-20 right-6 z-30 pointer-events-none">
        <div
            @class([
                'pointer-events-auto inline-flex items-center gap-x-2 rounded-full px-3 py-1 text-xs font-medium shadow-sm ring-1',
                'bg-white text-gray-500 ring-gray-300/60 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10' => $this->saveStatus === 'saved',
                'bg-red-50 text-red-700 ring-red-300/60 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-400/30' => $this->saveStatus === 'error',
            ])
            title="{{ $this->saveError }}"
        >
            <span
                @class([
                    'inline-block h-2 w-2 rounded-full',
                    'bg-emerald-500' => $this->saveStatus === 'saved',
                    'bg-red-500' => $this->saveStatus === 'error',
                ])
            ></span>
            <span>
                @if ($this->saveStatus === 'error')
                    Fehler
                @else
                    Gespeichert
                @endif
            </span>
        </div>
    </div>

    {{-- Top bar: Vorlage / Bezeichnung / Basiert auf --}}
    @if ($topBar)
        <div>{{ $topBar }}</div>
    @endif

    {{-- Body: Parameter (left) | Preview (right) --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 mt-6">
        {{-- Parameters --}}
        <div class="lg:col-span-5">
            @if ($parameters)
                {{ $parameters }}
            @endif
        </div>

        {{-- Preview --}}
        <div class="lg:col-span-7">
            <div class="sticky top-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @php
                    $pages = $this->templatePages();
                    $dims = $this->templateDimensions();
                    $url = $this->previewUrl();
                @endphp

                <div
                    x-data="{
                        loading: false,
                        natural: {{ $dims ? round($dims['width_mm'] * 7.56) : 280 }},
                        widthPx() {
                            if ($wire.zoom === 'fit') return '100%';
                            const factor = parseFloat($wire.zoom);
                            return (this.natural * factor) + 'px';
                        },
                    }"
                >
                    {{-- Page carousel + zoom toolbar --}}
                    <div class="mb-4 flex items-center justify-between gap-x-4 flex-wrap">
                        <div class="flex items-center gap-x-2">
                            <button
                                type="button"
                                wire:click="previousPage"
                                class="fi-btn rounded-md border border-gray-300 dark:border-white/10 px-2 py-1 text-sm hover:bg-gray-50 dark:hover:bg-white/5"
                                @disabled(count($pages) < 2)
                            >
                                ←
                            </button>
                            <div class="flex flex-wrap items-center gap-1">
                                @foreach ($pages as $key => $_)
                                    <button
                                        type="button"
                                        wire:click="goToPage('{{ $key }}')"
                                        @class([
                                            'rounded-md px-3 py-1 text-sm font-medium',
                                            'bg-primary-600 text-white' => $this->currentPage === $key,
                                            'border border-gray-300 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5' => $this->currentPage !== $key,
                                        ])
                                    >
                                        {{ ucfirst($key) }}
                                    </button>
                                @endforeach
                            </div>
                            <button
                                type="button"
                                wire:click="nextPage"
                                class="fi-btn rounded-md border border-gray-300 dark:border-white/10 px-2 py-1 text-sm hover:bg-gray-50 dark:hover:bg-white/5"
                                @disabled(count($pages) < 2)
                            >
                                →
                            </button>
                        </div>

                        <div class="flex items-center gap-x-1">
                            @foreach (['fit' => 'Fit', '0.5' => '50%', '1' => '100%', '2' => '200%'] as $value => $lbl)
                                <button
                                    type="button"
                                    x-on:click="$wire.zoom = '{{ $value }}'"
                                    x-bind:class="$wire.zoom === '{{ $value }}'
                                        ? 'bg-primary-600 text-white'
                                        : 'border border-gray-300 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'"
                                    class="rounded-md px-2 py-1 text-xs font-medium"
                                >
                                    {{ $lbl }}
                                </button>
                            @endforeach
                            <button
                                type="button"
                                wire:click="reloadPreview"
                                title="Vorschau neu laden"
                                class="ml-1 rounded-md border border-gray-300 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 px-2 py-1 text-xs font-medium"
                            >
                                ↻
                            </button>
                            @if ($dims)
                                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $dims['width_mm'] }} × {{ $dims['height_mm'] }} mm
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Live PNG preview rendered by Browsershot --}}
                    @if ($url && $dims)
                        <div class="flex justify-center items-start bg-gray-100 dark:bg-gray-950 p-6 rounded-lg overflow-auto">
                            <div class="relative" x-bind:style="`width: ${widthPx()}; max-width: 100%;`">
                                <div style="aspect-ratio: {{ $dims['width_mm'] }} / {{ $dims['height_mm'] }};
                                            background: white;
                                            border: 1px solid rgba(0,0,0,0.08);
                                            box-shadow: 0 8px 24px rgba(0,0,0,0.08);">
                                    <img
                                        src="{{ $url }}"
                                        alt="Vorschau"
                                        style="width: 100%; height: 100%; display: block;"
                                        x-on:load="loading = false"
                                        x-on:error="loading = false"
                                        x-init="loading = true"
                                        wire:key="preview-{{ $this->currentPage }}-{{ $this->record->updated_at?->getTimestamp() }}-{{ $this->previewBust }}"
                                    />
                                    <div
                                        x-show="loading"
                                        x-transition.opacity
                                        class="absolute inset-0 flex items-center justify-center bg-white/60 dark:bg-black/40 text-xs text-gray-600 dark:text-gray-300"
                                    >
                                        Aktualisiere Vorschau…
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            Keine Vorschau verfügbar.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-filament-panels::page>
