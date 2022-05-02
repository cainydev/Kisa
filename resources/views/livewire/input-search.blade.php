<div x-data="{open:false}"
     x-on:click.away="open = false"
     x-on:keydown.esc.window="open = false"
     class="relative flex flex-col items-stretch w-full max-w-xs">
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
            @foreach(App\Models\Product::where('name', 'like', "%$query%")->get()->filter(function($p){ return
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
