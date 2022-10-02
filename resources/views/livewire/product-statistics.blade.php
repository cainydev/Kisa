<div>
    <div class="p-4 mt-3 bg-white rounded-md shadow">
        <div class="flex items-center justify-between p-3 mb-3 space-y-1 border rounded-md">
            <p class="text-lg font-semibold">Statistik generiert für Produkt {{ $product->name }}</p>
            <button class="btn btn-primary"
                    wire:click="printPDF({{ $product->id }})">PDF Drucken</button>
        </div>

        <x-product-statistic :product="$product" />
    </div>
</div>
