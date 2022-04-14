{{-- Bio Inspection Modal --}}
<div x-data="{open: false}" x-on:keydown.esc.window="open = false">
    {{-- Trigger --}}
    <button x-on:click="open = true;"
            class="px-2 py-1 font-semibold border rounded hover:bg-gray-100 text-dark">Ansehen</button>

    {{-- Modal --}}
    <div style="display:none"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="bg-opacity-0 opacity-0 scale-90"
         x-transition:enter-end="bg-opacity-50 opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="bg-opacity-50 opacity-100 scale-100"
         x-transition:leave-end="bg-opacity-0 opacity-0 scale-90"
         x-show="open"
         class="absolute inset-0 z-50 flex items-center justify-center transition-all bg-black bg-opacity-50">
        <div class="max-w-lg p-4 bg-white rounded text-dark">
            <span class="flex items-center justify-between mb-2 space-x-4">
                <p class="text-xl font-semibold">Bio-Eingangskontrolle</p>
                <button type="button"
                        x-on:click="open = false"
                        class="rounded hover:bg-gray-100"
                        aria-label="Close">
                    <x-icons.icon-x class="w-6 h-6 m-1 stroke-gray-800" />
                </button>
            </span>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Kontrolle</th>
                        <th scope="col">Wert</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inspection as $key => $value)
                    <tr>
                        <th>{{ __($key) }}</th>
                        <td>@if($value == '1' || $value == '0')
                            {{ boolval($value) ? 'Ja' : 'Nein' }}
                            @else
                            {{ $value }}
                            @endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
