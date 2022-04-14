<div class="flex flex-col mt-8 bg-white rounded">
    <p class="mb-2 font-bold tracking-wider uppercase">Rezept für</p>
    <x-recipe-title :position="$position" />


    @if (session()->has('success'))
    <div class="my-3 alert alert-success"
         role="alert">
        {{ session('success') }}
    </div>
    @endif

    @if (session()->has('warning'))
    <div class="my-3 alert alert-danger"
         role="alert">
        {{ session('warning') }}
    </div>
    @endif

    @if (session()->has('error'))
    <div class="my-3 alert alert-warning"
         role="alert">
        {{ session('error') }}
    </div>
    @endif

    {{-- Recipe itself --}}
    <p class="mt-2 mb-2 font-bold tracking-wider uppercase">Zutaten</p>
    <div class="accordion"
         id="wrapper">
        @foreach($position->variant->product->herbs as $herb)
        <div class="accordion-item">
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
                        <x-icons.icon-checkmark-solid class="w-8 h-8 p-1 ml-4 bg-white rounded-full fill-green-600"/>
                    @endif
                </button>
            </h2>
            <div id="collapse{{ $loop->index }}"
                 class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                 aria-labelledby="heading{{ $loop->index }}"
                 data-bs-parent="#wrapper">

                {{-- Akkordeon Body --}}
                <div class="accordion-body">
                    <p>{{ $herb->name }} kommt mit {{ sprintf('%.1f%%', $herb->pivot->percentage) }} in dieser Mischung
                        vor. Bitte wähle ein Gebinde aus, welches du zum Abfüllen verwenden willst.</p>

                    @php
                    $gesamt = ($position->variant->size * $position->count) * ($herb->pivot->percentage / 100);
                    @endphp
                    <p class="text-xl">Insgesamt: {{ $gesamt }}g</p>

                    {{-- Verfügbare Säcke --}}
                    <div class="grid grid-cols-2 gap-6 mt-4 xl:grid-cols-3">
                        @forelse($herb->bags->filter(fn($b) => $b->getCurrent() >= $gesamt)->sortBy('bestbefore') as
                        $bag)
                        {{-- Sack Card --}}
                        <button wire:click="setBag({{ $bag->id }})"
                                class="flex flex-col self-start max-w-lg ring-green-600 rounded {{ $position->isBagFor($bag, $herb) ? 'ring-4' : 'ring-0' }}"
                                wire:key="bag{{ $bag->id }}">
                            <div class="flex items-center justify-between w-full p-3 bg-gray-100">
                                <p>{{ $bag->specification }}</p>
                                @if($bag->bio)
                                <p class="text-green-500">BIO</p>
                                @else
                                <p class="text-red-500"><s>BIO</s></p>
                                @endif
                            </div>
                            <div class="p-3">
                                <p>Haltbar bis {{ $bag->bestbefore->diffForHumans() }}</p>
                                <p class="text-xs text-red-500">@if($bag->bestbefore < now()->addMonths(3)) Läuft bald
                                        ab! @endif</p>
                            </div>
                            <div class="flex flex-col items-stretch justify-between p-3">
                                <p class="mb-2">Charge: {{ $bag->charge }}</p>
                                <p class="text-sm">Füllstand: {{ $bag->getCurrent() }}g / {{ $bag->size }}g</p>
                                <span class="w-full mt-1 border rounded">
                                    <span class="block h-3"
                                          style="width:{{ ($bag->getCurrent() / $bag->size) * 100 }}%; background: hsl({{ ($bag->getCurrent() / $bag->size)*120 }}, 80%, 40%)"></span>
                                </span>
                            </div>
                        </button>
                    @empty
                    <p class="mt-4 col-span-full alert alert-danger">Es sind momentan keine Säcke {{ $herb->name }}
                        gelagert die noch die gebrauchte Menge von {{ $gesamt }}g haben.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
    @endforeach
</div>
</div>
