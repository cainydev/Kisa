<div class="flex flex-wrap gap-1 px-3 py-3.5">
    @forelse($getRecord()->variants as $v)
        <span class="px-2 text-sm py-0.5 bg-white dark:bg-transparent dark:text-white dark:border-gray-700 border rounded-full shadow-sm whitespace-nowrap w-min">{{ $v->size }}g</span>
    @empty
        <p>Keines</p>
    @endforelse
</div>
