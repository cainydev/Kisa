@if ($processing)
    <section
        wire:poll.1s="pollExtraction"
        class="fi-section rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
    >
        <div class="fi-section-header flex items-center gap-3 px-6 py-4">
            <x-filament::loading-indicator class="h-5 w-5 text-primary-500" />
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Dokument wird verarbeitet…
                </h3>
                <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                    Die Felder werden automatisch befüllt, sobald die Analyse abgeschlossen ist. Sie können in der Zwischenzeit weiterarbeiten.
                </p>
            </div>
        </div>
    </section>
@endif
