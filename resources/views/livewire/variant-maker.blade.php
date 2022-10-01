<div class="mb-16">
    <div class="p-4 bg-white rounded shadow-sm">
        <p>Hier kannst du Varianten hinzufügen und entfernen.</p>

        {{-- Flash messages --}}
        <span>
            @if(session()->has('success'))
            <div class="mt-2 bg-green-300 border-2 border-green-600 alert"
                 role="alert">
                {!! session('success') !!}
            </div>
            @endif

            @if(session()->has('error'))
            <div class="mt-2 bg-red-300 border-2 border-red-600 alert"
                 role="alert">
                {!! session('error') !!}
            </div>
            @endif
        </span>

        <div class="grid gap-8 xl:grid-cols-2">
            @if($product->exists)
            <div class="flex flex-col justify-start gap-3 p-6 mt-3 rounded shadow">
                <h2 class="pb-2 text-lg font-semibold">Neue Variante</h2>
                <div class="flex flex-col gap-1">
                    <span class="flex items-center">
                        <p class="text-xs">Größe in g:</p>
                        <sup class="text-danger">*</sup>
                    </span>
                    <span class="flex items-center">
                        <span class="flex items-stretch">
                            <input type="number"
                                   wire:model="size"
                                   class="rounded-l form-control" />
                            <div class="flex items-center pl-1 pr-2 bg-gray-100 border rounded-r">
                                <p>g</p>
                            </div>
                        </span>
                    </span>

                </div>
                <div class="flex flex-col gap-1">
                    <span class="flex items-center">
                        <p class="text-xs">SKU-Zusatz (Shopware):</p>
                        <sup class="text-danger">*</sup>
                    </span>

                    <span class="flex items-center gap-3">
                        <span class="flex items-stretch">
                            <div class="flex items-center pl-2 pr-1 bg-gray-100 border rounded-l">
                                <p>{{ $product->mainnumber }}</p>
                            </div>
                            <input type="text"
                                   wire:model="sku"
                                   class="rounded-r form-control" />
                        </span>
                    </span>
                    <span class="text-red-600">
                        @error('sku')
                        {{ $message }}
                        @enderror
                    </span>
                </div>
                <button wire:click="add"
                        class="flex items-center gap-2 px-2 py-1 font-semibold text-white bg-green-600 border rounded whitespace-nowrap w-min">
                    <x-icons.plus.solid class="w-5 h-5 fill-white" />Hinzufügen
                </button>
            </div>

            <div class="flex flex-col gap-4 p-6 mt-3 rounded shadow">
                <span class="pb-2">
                    <h2 class="text-lg font-semibold">Aktive Varianten</h2>
                    <span class="text-red-600">
                        @error('product.variants.*.ordernumber')
                        {{ $message }}
                        @enderror
                    </span>
                </span>

                @if($product->variants->count() > 0)
                @foreach($product->variants as $variant)

                <span
                      class="flex items-center justify-between gap-3 px-3 py-2 font-semibold bg-gray-100 border rounded shadow-sm">

                    <div class="flex items-stretch">
                        <input type="text"
                               class="w-24 rounded-l form-input"
                               wire:model='product.variants.{{ $loop->index }}.size' />
                        <span class="flex items-center px-2 border rounded-r">g</span>

                    </div>

                    <div class="flex items-stretch">
                        <span class="flex items-center px-2 border rounded-l">{{ $product->mainnumber }}</span>
                        <input type="text"
                               class="w-24 rounded-r form-input"
                               wire:model='product.variants.{{ $loop->index }}.ordernumber' />
                    </div>

                    <button wire:click="remove({{ $variant->id }})"
                            class="flex space-x-2 btn btn-danger">
                        Löschen
                    </button>

                </span>

                @endforeach
                @else
                <p>Noch keine Varianten vorhanden.</p>
                @endif
            </div>

        </div>

        @else
        <p class="text-red-500">Bitte speichere das Produkt erst ab, bevor du Varianten veränderst.</p>
        @endif
    </div>

</div>
