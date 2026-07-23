@php
    /**
     * @var array<string, mixed>|null $summary  Certificate display data (frozen snapshot or live-resolved), or null if none.
     * @var bool $frozen  True when showing the committed snapshot; false for a live/preview resolution.
     * @var bool $pending  True on edit when supplier/date changed — this preview replaces the snapshot on save.
     */
    $summary = $summary ?? null;
    $frozen = $frozen ?? false;
    $pending = $pending ?? false;
    $replacesExisting = $replacesExisting ?? false;
    $supplierUrl = $supplierUrl ?? null;
@endphp

@if ($pending && $summary !== null)
    <div class="mb-2 flex items-center gap-2 text-xs font-medium text-primary-600 dark:text-primary-400">
        <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4" />
        @if ($replacesExisting)
            Wird beim Speichern durch dieses Zertifikat ersetzt:
        @else
            Wird beim Speichern übernommen:
        @endif
    </div>
@endif

@if ($summary === null)
    <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 dark:border-warning-500/30 dark:bg-warning-500/10">
        <div class="flex items-start gap-3">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning-600 dark:text-warning-400" />
            <div class="text-sm text-warning-800 dark:text-warning-200">
                <p class="font-semibold">Kein gültiges Zertifikat für dieses Datum.</p>
                <p class="mt-1 text-warning-700 dark:text-warning-300">
                    @if ($pending && $replacesExisting)
                        Für den gewählten Lieferanten und das Datum gibt es kein gültiges Zertifikat.
                        Beim Speichern wird das bisher eingefrorene Zertifikat entfernt.
                    @elseif ($pending)
                        Für den gewählten Lieferanten und das Datum gibt es kein gültiges Zertifikat.
                    @elseif ($frozen)
                        Für diese Lieferung wurde kein Zertifikat eingefroren. Sobald ein passendes
                        Zertifikat beim Lieferanten hinterlegt ist, kann es über „Zertifikat nachtragen“
                        übernommen werden.
                    @else
                        Der Lieferant hat kein Zertifikat, das dieses Lieferdatum abdeckt. Die Lieferung
                        kann angelegt werden – sobald das Zertifikat hinterlegt ist, wird es beim
                        nächsten Speichern automatisch übernommen.
                    @endif
                </p>
                @if ($supplierUrl)
                    <a href="{{ $supplierUrl }}"
                       class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-warning-700 underline hover:text-warning-600 dark:text-warning-300">
                        <x-filament::icon icon="heroicon-o-plus-circle" class="h-4 w-4" />
                        Zertifikat beim Lieferanten hinterlegen
                    </a>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
        <div class="mb-3 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-s-shield-check" class="h-5 w-5 text-success-600 dark:text-success-400" />
                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                    @if ($frozen)
                        Zertifikat (eingefroren bei Wareneingang)
                    @elseif ($pending)
                        Neues Zertifikat (nach dem Speichern)
                    @else
                        Wird zertifiziert unter
                    @endif
                </span>
            </div>
            <span class="inline-flex items-center rounded-md bg-gray-200 px-2 py-0.5 font-mono text-xs text-gray-700 dark:bg-white/10 dark:text-gray-200">
                {{ $summary['control_body_code'] }}
            </span>
        </div>

        <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Zertifikatsnummer</dt>
                <dd class="font-mono text-gray-900 dark:text-gray-100">{{ $summary['certificate_number'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Kontrollstelle</dt>
                <dd class="text-gray-900 dark:text-gray-100">{{ $summary['control_body'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Gültigkeit</dt>
                <dd class="text-gray-900 dark:text-gray-100">
                    {{ $summary['valid_from'] ? \Illuminate\Support\Carbon::parse($summary['valid_from'])->format('d.m.Y') : '—' }}
                    –
                    {{ $summary['valid_until'] ? \Illuminate\Support\Carbon::parse($summary['valid_until'])->format('d.m.Y') : '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Ausgestellt</dt>
                <dd class="text-gray-900 dark:text-gray-100">
                    {{ $summary['issued_at'] ? \Illuminate\Support\Carbon::parse($summary['issued_at'])->format('d.m.Y') : '—' }}
                    @if ($summary['issued_place']) · {{ $summary['issued_place'] }} @endif
                </dd>
            </div>
            @if (! empty($summary['activities']))
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400">Tätigkeiten</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ implode(', ', $summary['activities']) }}</dd>
                </div>
            @endif
            @if (! empty($summary['product_categories']))
                <div class="sm:col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400">Erzeugniskategorien</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ implode(', ', $summary['product_categories']) }}</dd>
                </div>
            @endif
        </dl>

        @if ($frozen && ($summary['document'] ?? null))
            <div class="mt-4 flex items-center gap-3 border-t border-gray-200 pt-3 dark:border-white/10">
                <a href="{{ $summary['document']->getUrl() }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-document-text" class="h-4 w-4" />
                    PDF ansehen
                </a>
                <a href="{{ $summary['document']->getUrl() }}" download
                   class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-500 dark:text-gray-300">
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
                    Herunterladen
                </a>
                <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">Nicht editierbar</span>
            </div>
        @elseif (! $frozen)
            <p class="mt-3 border-t border-gray-200 pt-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                Dieses Zertifikat wird beim Speichern automatisch als unveränderliche Kopie übernommen.
            </p>
        @endif
    </div>
@endif
