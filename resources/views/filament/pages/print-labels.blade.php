<x-filament-panels::page>
    {{ $this->form }}

    @php
        $targets = $this->targets;
    @endphp

    @if (! empty($this->data['subject_type']) && ! empty($this->data['subject_id']))
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header flex items-center gap-x-3 px-6 py-4 border-b border-gray-200 dark:border-white/10">
                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Verfügbare Etiketten
                </h3>
            </div>
            <div class="fi-section-content p-6 space-y-4">
                @forelse ($targets as $t)
                    @php
                        /** @var \App\Labels\LabelTemplate $template */
                        $template = $t['template'];
                        /** @var \App\Models\Label|null $label */
                        $label = $t['label'];
                        $kind = $t['kind'];
                        $pages = $t['pages'];
                    @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-white/10 p-4">
                        <div class="flex items-start justify-between gap-x-4">
                            <div>
                                <div class="text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $label?->name ?: $template->name() }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    @if ($kind === 'configured')
                                        Konfiguriert · Vorlage „{{ $template->name() }}"
                                    @elseif ($kind === 'bare')
                                        Direkter Druck · Vorlage „{{ $template->name() }}"
                                    @else
                                        {{ $t['reason'] ?? 'Konfiguration erforderlich' }}
                                    @endif
                                </div>
                            </div>

                            @if ($kind === 'configured' && $label)
                                <a
                                    class="fi-btn fi-btn-color-gray inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 ring-1 ring-gray-300 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/5"
                                    href="{{ \App\Filament\Resources\Labels\LabelResource::getUrl('edit', ['record' => $label->id]) }}"
                                >
                                    Konfigurieren
                                </a>
                            @elseif ($kind === 'unconfigured')
                                <a
                                    class="fi-btn fi-btn-color-primary inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500"
                                    href="{{ \App\Filament\Resources\Labels\LabelResource::getUrl('create', ['template_key' => $template->key()]) }}"
                                >
                                    Konfigurieren
                                </a>
                            @endif
                        </div>

                        @if ($kind !== 'unconfigured')
                            @php
                                $dims = $template->dimensions();
                            @endphp
                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ($pages as $pageKey => $_view)
                                    <div class="rounded-md bg-gray-50 dark:bg-white/5 p-3">
                                        <div class="mb-2 flex items-center justify-between gap-x-2">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ ucfirst($pageKey) }}
                                            </div>
                                            <div class="flex items-center gap-x-2">
                                                {{ ($this->pdfAction)(['template' => $template->key(), 'label' => $label?->id, 'page' => $pageKey]) }}
                                                {{ ($this->printReadyPdfAction)(['template' => $template->key(), 'label' => $label?->id, 'page' => $pageKey]) }}
                                            </div>
                                        </div>
                                        @if ($label)
                                            @php
                                                $previewUrl = route('labels.preview', ['label' => $label->id, 'page' => $pageKey]).'?v='.$label->updated_at?->getTimestamp();
                                            @endphp
                                            <a
                                                href="{{ $previewUrl }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="flex justify-center bg-white dark:bg-gray-900 rounded p-2 ring-1 ring-gray-200 dark:ring-white/10 hover:ring-primary-500 dark:hover:ring-primary-400 transition"
                                            >
                                                <img
                                                    src="{{ $previewUrl }}"
                                                    alt="{{ $template->name() }} {{ $pageKey }}"
                                                    style="aspect-ratio: {{ $dims['width_mm'] }} / {{ $dims['height_mm'] }};
                                                           width: 100%; max-width: {{ ($dims['width_mm'] / max($dims['width_mm'], $dims['height_mm'])) * 240 }}px;
                                                           height: auto; display: block;"
                                                    loading="lazy"
                                                />
                                            </a>
                                        @else
                                            <div class="rounded bg-white/40 dark:bg-white/5 p-3 text-xs text-gray-500 dark:text-gray-400">
                                                Vorschau nur für konfigurierte Etiketten verfügbar.
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Keine Etiketten verfügbar für diese Auswahl.
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</x-filament-panels::page>
