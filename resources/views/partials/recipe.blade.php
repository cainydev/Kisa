<div class="flex flex-col p-8 mt-6 bg-white rounded">
    <div class="mb-8">
        <p class="mb-4 font-bold tracking-wider uppercase">Rezept</p>
        <div class="flex text-2xl">
            <span class="p-2 font-semibold text-white rounded-l bg-kgreen">{{ $position->count }}x</span>
            <span class="p-2 font-semibold bg-gray-100">{{ $position->variant->product->name }}</span>
            <span class="p-2 font-semibold text-white rounded-r bg-kgreen">{{ sprintf('%ug', $position->variant->size)
                }}</span>
        </div>
    </div>

    {{-- Recipe itself --}}
    <p class="mb-4 font-bold tracking-wider uppercase">Zutaten</p>
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
                </button>
            </h2>
            <div id="collapse{{ $loop->index }}"
                 class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                 aria-labelledby="heading{{ $loop->index }}"
                 data-bs-parent="#wrapper">
                <div class="accordion-body">
                    <p>{{ $herb->name }} kommt mit {{ sprintf('%.1f%%', $herb->pivot->percentage) }} in dieser Mischung
                        vor.</p>
                    <h3 class="text-xl">Aktuell abgefüllt: {{ ($position->variant->size * $position->count) *
                        ($herb->pivot->percentage / 100) }}g</h3>
                    <div class="flex items-center gap-6 p-0 mt-3 border rounded">
                        <span class="block h-3"
                              style="width:">

                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-6 mt-4">

                        @forelse($herb->bags->where('current', '>', 0)->sortBy('bestbefore') as $bag)
                        <div class="flex flex-col self-start max-w-lg border rounded"
                             x-data="{checked: false, amount: 0}">
                            <div class="flex items-center justify-between w-full p-3 bg-gray-100">
                                <p>{{ $bag->specification }}</p>
                                @if($bag->bio)
                                <p class="text-green-500">BIO</p>
                                @else
                                <p class="text-red-500"><s>BIO</s></p>
                                @endif
                            </div>
                            <div class="px-2 py-3">
                                <p>Haltbar bis {{ $bag->bestbefore->diffForHumans() }}</p>
                                <p class="text-xs text-red-500">@if($bag->bestbefore < now()->addMonths(3)) Läuft bald
                                        ab! @endif</p>
                            </div>
                            <div class="flex flex-col items-stretch justify-between px-2 my-2">
                                <p class="mb-2">Charge: {{ $bag->charge }}</p>
                                <p class="text-sm">Füllstand: {{ $bag->current }}g / {{ $bag->size }}g</p>
                                <span class="w-full mt-1 border rounded">
                                    <span class="block h-3"
                                          style="width:{{ ($bag->current / $bag->size) * 100 }}%; background: hsl({{ ($bag->current / $bag->size)*120 }}, 80%, 40%)"></span>
                                </span>

                            </div>
                            <div class="flex items-center justify-between px-2 mb-3">
                                <input type="number"
                                       x-model="amount"
                                       class="px-2 py-1 rounded"
                                       placeholder="Menge"
                                       min="0"
                                       max="{{ $bag->current }}" />
                                {{ $position->id }}{{ $bag->id }}
                                <button class="flex justify-center py-1 ml-2 text-green-600 bg-gray-300 border rounded hover:disabled:bg-gray-300 hover:bg-gray-100 disabled:text-gray-200 grow"
                                        :disabled="amount == 0"
                                        x-on:click="
                                        fetch('http://localhost:8000/api/pos/{{ $position->id }}/{{ $bag->id }}/' + amount,
                                        { method: 'POST'}).then(res => { console.log('Request complete! response:', res); });">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         width="24"
                                         height="24"
                                         viewBox="0 0 24 24"
                                         fill="none"
                                         stroke="currentColor"
                                         stroke-width="2"
                                         stroke-linecap="round"
                                         stroke-linejoin="round"
                                         class="feather feather-check-circle">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @empty
                        <p class="mt-4 col-span-full alert alert-danger">Es sind momentan keine Säcke {{ $herb->name }}
                            gelagert.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
        @endforeach
    </div>
</div>
