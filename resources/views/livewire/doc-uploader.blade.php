{{-- Because she competes with no one, no one can compete with her. --}}

<div class="flex flex-col border rounded">
    <div class="flex items-center justify-between px-3 py-2 border-b shadow-inner wrap">
        <label class="mb-0 form-label">{{ $title }}</label>
        <span>
            <button class="btn btn-info" title="Download">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z" />
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z" />
                </svg>
            </button>
            <button class="btn btn-danger" title="Löschen">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" />
                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                </svg>
            </button>
        </span>
    </div>

    <div class="px-3 py-2">
        @if($entity->hasMedia($collection))

        @if($entity->getFirstMedia($collection)->hasGeneratedConversion('thumb'))
        <div x-data="{open: false, url: {{ $entity->getFirstMediaUrl($collection, 'big'); }}}" x-on:click="open = true">
            <img src="{{ $entity->getFirstMediaUrl($collection, 'small'); }}" alt="Vorschau {{ $title }}">
            <div x-if="open" class="fixed">
                <img x-bind:src="url" alt="{{ $title }} in Groß">
            </div>
        </div>
        @else
        <p>Es konnte keine Vorschau für die Datei erstellt werden.</p>
        @endif

        @else
        <input type="file" wire:model="document" class="p-2 form-control" />
        @endif
    </div>

</div>
