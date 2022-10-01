<div class="flex flex-col mt-8 bg-white rounded">

    {{-- Settings of this bottle --}}
    <div class="grid gap-2 lg:grid-cols-2">
        <div>
            <p class="mb-2 font-bold tracking-wider uppercase">Abfüllung von</p>
            <x-recipe-title :position="$position" />
            {{-- Current stock display --}}
            <div class="p-2 mt-12 border rounded-md w-fit">
                <span class="flex flex-row items-center shadow h-9 w-min">
                    <div class="h-full overflow-hidden rounded-l-md bg-billbee w-9">
                        <img wire:loading.remove
                             class="h-full "
                             src="{{ asset('images/billbee.png') }}"
                             alt="">
                        <x-icons.icon-loading wire:loading.flex
                                              class="fill-white" />
                    </div>
                    <button class="h-full px-3 font-semibold bg-gray-200 rounded-r-md"
                            wire:click="refreshStock">Aktualisieren</button>
                </span>
                <p class="mt-2 text-lg">Aktueller Bestand in Billbee: {{ $position->variant->stock }} </p>
            </div>

        </div>
        <div class="flex flex-col">
            <p class="mb-2 font-bold tracking-wider uppercase">Einstellungen</p>
            {{-- Set charge manually --}}
            <p class="form-label">Charge</p>
            <div class="flex justify-start">
                <input wire:model="newCharge"
                       type="text"
                       class="mr-3 rounded form-control" />
                <button wire:click="setCharge"
                        class="mr-3 btn btn-success whitespace-nowrap">Speichern</button>
                <button wire:click="generateCharge"
                        class="btn btn-primary whitespace-nowrap">Auto-Charge</button>
            </div>

            {{-- Upload to Billbee --}}
            <div class="flex flex-col mt-3">
                <p class="form-label">Upload zu Billbee</p>
                @if(!$position->uploaded)
                @if($position->hasAllBags())
                <button wire:click="uploadToBillbee"
                        class="mr-3 btn btn-success whitespace-nowrap">
                    <span wire:loading.remove>Produkt einlagern in Billbee</span>
                    <x-icons.icon-loading wire:loading.flex
                                          class="w-8 h-8 mx-auto fill-white" />
                </button>
                @else
                <div class="alert alert-warning"
                     role="alert">
                    <p>Fülle zuerst das ganze Produkt ab bevor du es in Billbee einlagern kannst.</p>
                </div>
                @endif
                @else
                <div class="alert alert-success"
                     role="alert">
                    <p>Diese Abfüllung wurde bereits in Billbee eingelagert.</p>
                </div>
                @endif
            </div>

        </div>


        <div class="col-span-full">
            @if (session()->has('success'))
            <div class="alert alert-success"
                 role="alert">
                {{ session('success') }}
            </div>
            @endif

            @if (session()->has('warning'))
            <div class="alert alert-warning"
                 role="alert">
                {{ session('warning') }}
            </div>
            @endif

            @if (session()->has('error'))
            <div class="alert alert-danger"
                 role="alert">
                {{ session('error') }}
            </div>
            @endif
        </div>


        {{-- Recipe itself --}}
        <p class="font-bold tracking-wider uppercase">Zutaten</p>
        <div class="accordion col-span-full"
             id="wrapper">

            @php
            $show = false;
            @endphp

            @forelse($position->variant->product->herbs as $herb)
            <div class="accordion-item"
                 x-data>
                <h2 class="accordion-header"
                    id="heading{{ $loop->index }}">
                    <button class="flex justify-between accordion-button"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse{{ $loop->index }}"
                            aria-expanded="false"
                            aria-controls="collapse{{ $loop->index }}">
                        {{ $herb->name }}
                        @if($position->hasBagFor($herb))
                        <x-icons.icon-checkmark-solid class="w-8 h-8 p-1 ml-4 bg-white rounded-full fill-green-600" />
                        @endif
                    </button>
                </h2>

                @php
                $openTab = false;
                if(!$show){
                if(!$position->hasBagFor($herb)){
                $show = true;
                $openTab = true;
                }
                }
                @endphp

                <div id="collapse{{ $loop->index }}"
                     class="accordion-collapse collapse {{ $openTab ? 'show' : '' }}"
                     aria-labelledby="heading{{ $loop->index }}"
                     data-bs-parent="#wrapper">

                    {{-- Akkordeon Body --}}
                    <div class="accordion-body">
                        <p>{{ $herb->name }} kommt mit {{ sprintf('%.1f%%', $herb->pivot->percentage) }} in dieser
                            Mischung
                            vor. Bitte wähle ein Gebinde aus, welches du zum Abfüllen verwenden willst.</p>

                        @php
                        $gesamt = ($position->variant->size * $position->count) * ($herb->pivot->percentage / 100);
                        @endphp
                        <p class="text-xl">Insgesamt: {{ $gesamt }}g</p>

                        {{-- Verfügbare Säcke --}}
                        <div class="grid gap-6 mt-4 md:grid-cols-2 xl:grid-cols-3">
                            @forelse($herb->bags->sortBy('bestbefore') as $bag)
                            {{-- Sack Card --}}
                            <div class="flex flex-col max-w-lg ring-green-600 rounded {{ $position->isBagFor($bag, $herb) ? 'ring-4' : 'ring-0' }}"
                                 wire:key="bag{{ $bag->id }}">
                                <div class="flex items-center justify-between w-full p-3 bg-gray-100">
                                    <p @if($bag->getCurrentWithTrashed() < $gesamt)
                                          class="text-red-600"
                                          @endif>{{ $bag->specification }}</p>
                                    @if($bag->bio)
                                    <p class="text-green-500">BIO</p>
                                    @else
                                    <p class="text-red-500">NICHT BIO</p>
                                    @endif

                                    @if($position->isBagFor($bag, $herb))
                                    <button title="Nicht mehr auswählen"
                                            class="btn btn-danger"
                                            wire:click="removeBag({{ $bag->id }})">
                                        <x-icons.icon-x class="stroke-white" />
                                    </button>
                                    @else
                                    <button title="Auswählen"
                                            class="btn btn-success"
                                            wire:click="setBag({{ $bag->id }})">
                                        <x-icons.icon-checkmark class="w-6 h-6 fill-white" />
                                    </button>
                                    @endif
                                </div>
                                <div class="p-3">
                                    <p>Haltbar bis {{ $bag->bestbefore->diffForHumans() }}</p>
                                    <p class="text-xs text-red-500">@if($bag->bestbefore < now()->addMonths(3)) Läuft
                                            bald
                                            ab! @endif</p>
                                </div>
                                @if ($bag->getCurrentWithTrashed() < $gesamt)
                                  <p
                                  class="pl-4 text-red-500">Achtung! Dieser Sack hat nicht die ausreichende
                                    Menge!
                                    </p>
                                    @endif
                                    <div class="flex flex-col items-stretch justify-between p-3">
                                        <p class="mb-2">Charge: {{ $bag->charge }}</p>
                                        <p
                                           class="text-sm {{ $bag->getCurrentWithTrashed() < $gesamt ? 'text-red-600' : '' }}">
                                            Füllstand: {{ $bag->getCurrentWithTrashed() }}g / {{
                                            $bag->size
                                            }}g</p>
                                        <span class="w-full mt-1 border rounded">
                                            <span class="block h-3"
                                                  style="width:{{ ($bag->getCurrentWithTrashed() / $bag->size) * 100 }}%; background: hsl({{ ($bag->getCurrentWithTrashed() / $bag->size)*120 }}, 80%, 40%)"></span>
                                        </span>
                                    </div>
                            </div>
                            @empty
                            <p class="mt-4 col-span-full alert alert-danger">Es sind momentan keine Säcke {{ $herb->name
                                }}
                                gelagert die noch die gebrauchte Menge von {{ $gesamt }}g haben.</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
            @empty
            <div class="mt-2">
                <p><span class="font-semibold text-red-500">Achtung:</span> Für dieses Produkt wurde noch kein Rezept
                    hinterlegt.</p>
                <a href="{{ route('platform.products.edit', $position->variant->product) }}">
                    <x-button class="mt-2 font-semibold">Produkt bearbeiten</x-button>
                </a>
            </div>
            @endforelse
        </div>
    </div>
</div>
