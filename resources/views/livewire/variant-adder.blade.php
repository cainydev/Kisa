<div class="my-16 grid lg:grid-cols-2 gap-8">
    <div>
        <legend class="text-black">Produkte hinzufügen</legend>

        <div class="p-4 bg-white rounded shadow-sm">
            @if($bottle->exists)
            <div x-data="{open:false}"
                 x-on:click.away="open = false"
                 x-on:keydown.esc.window="open = false"
                 class="relative flex flex-col items-stretch w-full max-w-xs">
                <span>@error('product') {{ $message }} @enderror</span>
                <button x-on:click.prevent="open = !open"
                        class="flex items-center justify-between w-full px-3 py-2 bg-gray-100 border rounded-t">
                    @if($product != null && $product->exists)
                    {{ $product->name }}
                    <x-icons.icon-checkmark-solid class="w-5 h-5 fill-green-600" />
                    @else
                    Produkt wählen
                    <x-icons.icon-x-solid class="w-5 h-5 fill-red-600" />
                    @endif
                </button>
                <span x-show="open"
                      class="absolute z-50 flex flex-col w-full bg-gray-100 border-b border-x top-10 max-h-64">
                    <input type="text"
                           wire:model="query"
                           class="w-full border-none shadow-inner"
                           placeholder="Suchen..">
                    <div class="overflow-y-scroll">
                        @foreach(App\Models\Product::where('name', 'like', "%$query%")->get()->filter(function($p){
                        return
                        $p->variants->count() > 0; }) as $options)
                        <button x-on:click="open = false"
                                wire:click="setProduct({{ $options->id }})"
                                class="@if($loop->even) bg-gray-200 shadow-inner @endif w-full px-3 py-2 text-left hover:bg-gray-600 hover:text-white">
                            {{ $options->name }}
                        </button>
                        @endforeach
                    </div>

                </span>
            </div>

            @if($product != null && $product->exists)
            <div x-data="{open:false}"
                 x-on:click.away="open = false"
                 x-on:keydown.esc.window="open = false"
                 class="relative flex flex-col items-stretch w-full max-w-xs mt-3">
                <button x-on:click.prevent="open = !open"
                        class="flex items-center justify-between w-full px-3 py-2 bg-gray-100 border rounded-t">
                    @if($variant != null && $variant->exists)
                    {{ $variant->size }}g
                    <x-icons.icon-checkmark-solid class="w-5 h-5 fill-green-600" />
                    @else
                    Variante wählen
                    <x-icons.icon-x-solid class="w-5 h-5 fill-red-600" />
                    @endif
                </button>
                <span x-show="open"
                      class="absolute z-50 flex flex-col w-full bg-gray-100 border-b border-x top-10 max-h-64">
                    <div class="overflow-y-scroll">
                        @foreach($product->variants as $option)
                        <button x-on:click="open = false"
                                wire:click="setVariant({{ $option->id }})"
                                class="w-full px-3 py-2 text-left hover:bg-gray-600 hover:text-white">
                            {{ $option->size }}g
                        </button>
                        @endforeach
                    </div>

                </span>
            </div>
            @if($variant != null && $variant->exists)
            <div>
                <input type="number"
                       class="max-w-xs mt-3 form-control"
                       placeholder="Anzahl"
                       min="1"
                       wire:model="count"
                       max="100"
                       name=""
                       id="">
            </div>
            <button wire:click="add"
                    class="mt-3 btn btn-success">
                Hinzufügen
            </button>
            @endif
            @endif
            @else
            <p>Bitte speichere zuerst die Abfüllung ab.</p>
            @endif

        </div>
    </div>
    <div>
        <legend class="text-black">Produkte dieser Abfüllung</legend>

        <div class="p-4 flex flex-col bg-white rounded shadow-sm">

            @forelse ($bottle->positions as $pos)
            <div class="@if($loop->even) bg-gray-100 @endif w-full grid gap-3 items-center grid-cols-4 px-2 py-1">
                <span class="col-span-3">{{ $pos->count }}x
                {{ $pos->variant->product->name }}</span>
                <span class="flex items-center justify-between">
                    @include('partials.variants', ['variants' => [$pos->variant]])
                    <button class="w-9 h-9 group" title="Löschen" wire:click="delete({{ $pos->id }})">
                        <x-icons.icon-x-solid class="fill-red-500 group-hover:fill-red-600" />
                    </button>
                </span>

            </div>
            @empty

            @endforelse
        </div>
    </div>
</div>
