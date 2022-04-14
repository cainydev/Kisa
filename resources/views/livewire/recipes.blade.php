<div x-data="{open: 0}">
    <ul class="nav nav-tabs">
        @foreach($bottle->positions as $position)
        <li class="nav-item"
            x-on:click="open = {{ $loop->index }}"
            x-bind:class="open == {{ $loop->index }} && 'active'"
            role="presentation">
            <button class="nav-link {{ $position->hasAllBags() ? '!text-green-500' : '!text-red-500' }}">
                {{ $position->variant->product->name}}
                <span class="px-1 ml-1 text-white rounded bg-kgreen">{{ $position->variant->size }}g</span>
            </button>
        </li>
        @endforeach
    </ul>
    <div class="tab-content">
        @foreach($bottle->positions as $position)
        <div class="tab-pane fade"
             x-bind:class="open == {{ $loop->index }} && 'show active'">
            @livewire('recipe', ['position' => $position], key($loop->index))
        </div>
        @endforeach
    </div>
</div>
