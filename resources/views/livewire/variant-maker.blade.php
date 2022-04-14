<div class="mb-16">
    <div class="p-4 bg-white rounded shadow-sm">
        <p>Hier kannst du Varianten hinzufügen und entfernen.</p>

        @if($product->exists)
        <div class="flex flex-col justify-start gap-3 mt-3">
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
            </div>
            <button wire:click="add" class="flex items-center gap-2 px-2 py-1 font-semibold text-white bg-green-600 border rounded whitespace-nowrap w-min"><x-icons.plus.solid class="w-5 h-5 fill-white" />Hinzufügen</button>
        </div>

        <div class="flex flex-wrap items-center gap-4 p-6 mt-3 rounded shadow">
            @if($product->variants->count() > 0)
            @foreach($product->variants as $variant)
            <span class="flex items-center gap-3 px-3 py-2 font-semibold bg-gray-100 border rounded-full shadow-sm">
                {{ $variant->size }}g ({{ $product->mainnumber.$variant->ordernumber }})
                <button wire:click="remove({{ $variant->id }})" class="border border-gray-100 rounded-full hover:bg-white hover:border-gray-300">
                    <x-icons.icon-x class="w-5 h-5 stroke-red-600" />
                </button>
            </span>
            @endforeach
            @else
            <p>Noch keine Varianten vorhanden.</p>
            @endif
        </div>

        @else
        <p class="text-red-500">Bitte speichere das Produkt erst ab, bevor du Varianten veränderst.</p>
        @endif
    </div>

</div>
