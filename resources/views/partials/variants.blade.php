<div class="flex flex-wrap gap-x-2 gap-y-1">
    @foreach($variants as $v)
    <span class="px-3 py-1 border rounded-full shadow-sm whitespace-nowrap w-min">{{ sprintf('%ug', $v->size) }}</span>
    @endforeach
</div>

