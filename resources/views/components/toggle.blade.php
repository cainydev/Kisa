@props(['attribute' => '', 'texts' => false])

<button x-data="{on: @entangle($attribute)}"
        x-on:click="on = !on"
        role="switch"
        x-bind:aria-checked="on"
        x-bind:class="on ? 'border-kgreen' : 'border-gray-300'"
        class="relative flex justify-end w-16 p-1 transition-colors border-2 rounded-full appearance-none">
    <span class="w-6 h-6 transition-colors rounded-full"
          x-bind:class="on ? 'bg-kgreen' : 'bg-gray-300'"></span>
    <span class="transition-flex motion-reduce:transition-none"
          x-bind:class="on || 'flex-1'"></span>

    @if ($texts)
    <span aria-hidden="true"
          class="absolute flex justify-around w-full -translate-x-1/2 -translate-y-1/2 left-1/2 top-1/2 h- -z-10">
        <p class="text-xs text-gray-400">on</p>
        <p class="text-xs text-gray-400">off</p>
    </span>
    @endif
</button>
