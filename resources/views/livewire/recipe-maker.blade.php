<div class="mb-16">
    <div class="p-4 bg-white rounded shadow-sm">
        <p>Hier kannst das Rezept für dieses Produkt bearbeiten.</p>

        {{-- Hinzufügen --}}
        <p class="mt-6 text-xl text-gray-800">Hinzufügen:</p>
        <div class="flex flex-col gap-2">
            {{-- Searchable Select --}}

            <div x-data="{open:false}"
                 x-on:click.away="open = false"
                 x-on:keydown.esc.window="open = false"
                 class="relative flex flex-col items-stretch w-full max-w-xs mt-2">
                <button x-on:click.prevent="open = !open"
                        class="flex items-center justify-between w-full px-3 py-2 bg-gray-100 border rounded-t">
                    @if($herb != null && $herb->exists)
                    {{ $herb->name }}
                    <x-icons.icon-checkmark-solid class="w-5 h-5 fill-green-600" />
                    @else
                    Rohstoff wählen
                    <x-icons.icon-x-solid class="w-5 h-5 fill-red-600" />
                    @endif
                </button>
                <span x-show="open"
                      class="absolute z-50 flex flex-col w-full bg-gray-100 border-b border-x top-10 max-h-64">
                    <input type="text"
                           wire:model="query"
                           class="w-full border-none"
                           placeholder="Suchen..">
                    <div class="overflow-y-scroll">
                        @foreach(App\Models\Herb::where('name', 'like', "%$query%")->get() as $herb)
                        <button x-on:click="open = false"
                                wire:click="setHerb({{ $herb->id }})"
                                class="w-full px-3 py-2 text-left hover:bg-gray-600 hover:text-white">
                            {{ $herb->name }}
                        </button>
                        @endforeach
                    </div>

                </span>
            </div>
            <div class="max-w-xs input-group">
                <input wire:model="amount"
                       type="number"
                       class="border-gray-300 rounded-l form-control"
                       placeholder="Anteil in %">
                <span class="input-group-text">%</span>
            </div>
            <button wire:click="add"
                    type="button"
                    class="w-full max-w-xs btn btn-success">Hinzufügen</button>
        </div>


        <p class="mt-6 mb-3 text-xl text-gray-800">Aktuelles Rezept:</p>
        @forelse ($product->herbs as $herb)
        <div class="@if($loop->even) bg-gray-100 @endif flex items-center justify-between w-full max-w-lg px-3 py-2">
            <p>{{ $herb->name }}</p>
            <p class="">{{ $herb->pivot->percentage }}%</p>
            <button class="btn btn-danger"
                    wire:click="detach({{ $herb->id }})">
                Löschen
            </button>
        </div>

        @empty
        <p>Für dieses Produkt wurde noch keine Rezeptur gespeichert.</p>
        @endforelse
        <div class="max-w-lg">
            <hr class="mt-3" />
            <span class="w-full text-right">
                @php
                $gesamt = $product->herbs->sum('pivot.percentage');
                @endphp
                <p class="mt-2 font-bold @if(floatval($gesamt) != 100.0) text-red-500 @else text-green-600 @endif">Prozent gesamt:
                    {{ $gesamt }}%</p>
            </span>
        </div>
    </div>
</div>
