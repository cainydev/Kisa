@php
use App\Models\Herb;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\TableSetting;
use App\Models\Supplier;
use App\Models\Delivery;
use App\Models\BioInspector;
use App\Models\Bag;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-white">
            {{ __('Bestand') }}
        </h2>
    </x-slot>

    <div class="z-20 py-12 bg-transparent"
         x-data="{open: 0}">
        <x-panel.wrapper>
            <x-panel.full class="flex gap-4 p-4">
                <x-button @click="open = 0; Livewire.emit('resetPage')"
                          x-bind:class="open == 0 ? 'border-b-kgreen focus:border-b-kgreen' : 'bg-gray-600'"
                          class="justify-center py-3 border-b-4 grow">
                    {{ TableSetting::firstWhere('tablename', 'herbs')->alias }}
                </x-button>
                <x-button @click="open = 1; Livewire.emit('resetPage')"
                          x-bind:class="open == 1 ? 'border-b-kgreen focus:border-b-kgreen' : 'bg-gray-600'"
                          class="justify-center py-3 border-b-4 grow">
                    {{ TableSetting::firstWhere('tablename', 'products')->alias }}
                </x-button>
                <x-button @click="open = 2; Livewire.emit('resetPage')"
                          x-bind:class="open == 2 ? 'border-b-kgreen focus:border-b-kgreen' : 'bg-gray-600'"
                          class="justify-center py-3 border-b-4 grow">
                    {{ TableSetting::firstWhere('tablename', 'bags')->alias }}
                </x-button>
                <x-button @click="open = 3; Livewire.emit('resetPage')"
                          x-bind:class="open == 3 ? 'border-b-kgreen focus:border-b-kgreen' : 'bg-gray-600'"
                          class="justify-center py-3 border-b-4 grow">
                    {{ TableSetting::firstWhere('tablename', 'product_types')->alias }}
                </x-button>
                <x-button @click="open = 4; Livewire.emit('resetPage')"
                          x-bind:class="open == 4 ? 'border-b-kgreen focus:border-b-kgreen' : 'bg-gray-600'"
                          class="justify-center py-3 border-b-4 grow">
                    {{ TableSetting::firstWhere('tablename', 'deliveries')->alias }}
                </x-button>
                <x-button @click="open = 5; Livewire.emit('resetPage')"
                          x-bind:class="open == 5 ? 'border-b-kgreen focus:border-b-kgreen' : 'bg-gray-600'"
                          class="justify-center py-3 border-b-4 grow">
                    {{ TableSetting::firstWhere('tablename', 'suppliers')->alias }}
                </x-button>
                <x-button @click="open = 6; Livewire.emit('resetPage')"
                          x-bind:class="open == 6 ? 'border-b-kgreen focus:border-b-kgreen' : 'bg-gray-600'"
                          class="justify-center py-3 border-b-4 grow">
                    {{ TableSetting::firstWhere('tablename', 'bio_inspectors')->alias }}
                </x-button>
            </x-panel.full>

            <div class="p-3 bg-white rounded col-span-full"
                 x-show="open == 0"
                 x-transition>
                @livewire('table-view', ['class' => Herb::class])
            </div>
            <div class="p-3 bg-white rounded col-span-full"
                 x-show="open == 1"
                 x-transition>
                @livewire('table-view', ['class' => Product::class])
            </div>
            <div class="p-3 bg-white rounded col-span-full"
                 x-show="open == 2"
                 x-transition>
                @livewire('table-view', ['class' => Bag::class])
            </div>
            <div class="p-3 bg-white rounded col-span-full"
                 x-show="open == 3"
                 x-transition>
                @livewire('table-view', ['class' => ProductType::class])
            </div>
            <div class="p-3 bg-white rounded col-span-full"
                 x-show="open == 4"
                 x-transition>
                @livewire('table-view', ['class' => Delivery::class, 'editView' => ''])
            </div>
            <div class="p-3 bg-white rounded col-span-full"
                 x-show="open == 5"
                 x-transition>
                @livewire('table-view', ['class' => Supplier::class])
            </div>
            <div class="p-3 bg-white rounded col-span-full"
                 x-show="open == 6"
                 x-transition>
                @livewire('table-view', ['class' => BioInspector::class])
            </div>
        </x-panel.wrapper>
    </div>
</x-app-layout>
