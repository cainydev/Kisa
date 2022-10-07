<div class="p-4 bg-white rounded shadow-sm">
    <h2 class="mb-3 text-lg font-semibold">Dokumente</h2>
    @if($delivery != null && $delivery->exists)
    <div class="grid gap-8 lg:grid-cols-3">
        @livewire('doc-uploader', [
        'entity' => $delivery,
        'collection' => 'invoice',
        'title' => 'Rechnung'
        ])
        @livewire('doc-uploader', [
        'entity' => $delivery,
        'collection' => 'deliveryNote',
        'title' => 'Lieferschein'
        ])
        @livewire('doc-uploader', [
        'entity' => $delivery,
        'collection' => 'certificate',
        'title' => 'Zertifikat'
        ])
    </div>
    @else
    <p>Bitte speichere die Lieferung erst ab, bevor du Dokumente hochlädst.</p>
    @endif
</div>
