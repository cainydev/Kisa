<div class="flex flex-col gap-2">
    <h2 class="text-lg font-semibold">{{ $objectName }} konnte nicht gelöscht werden:</h2>
    @foreach ($errors as $foreign => $count)
    <p>Fehler {{ ($loop->index + 1) }}: Tabelle {{ $foreign }} enthält <span
              class="px-1 text-xs text-center text-white bg-red-500 rounded-full text-mono">{{
            $count }}</span> Einträge, in denen diese(r) {{ $objectName }} referenziert wird.</p>
    @endforeach

    <p class="mt-2 text-xs">Um trotzdem zu löschen müssen erst alle entsprechenden Einträge in den anderen Tabellen
        gelöscht
        oder geändert werden.</p>
</div>
