@props(['position'])
<div>
    <div class="flex text-xl">
        <span class="p-2 font-semibold text-white rounded-l bg-kgreen">{{ $position->count }}x</span>
        <span class="p-2 font-semibold bg-gray-100">{{ $position->variant->product->name }}</span>
        <span class="p-2 font-semibold text-white rounded-r bg-kgreen">{{ sprintf('%ug', $position->variant->size)
            }}
        </span>
    </div>
</div>
