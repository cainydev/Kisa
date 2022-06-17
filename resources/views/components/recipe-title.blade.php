@props(['position'])
<div>
    <div class="flex text-xl shadow w-fit">
        <span class="px-2 py-1 font-semibold text-white rounded-l bg-kgreen">{{ $position->count }}x</span>
        <span class="px-2 py-1 font-semibold bg-gray-100">{{ $position->variant->product->name }}</span>
        <span class="px-2 py-1 font-semibold text-white rounded-r bg-kgreen">{{ sprintf('%ug', $position->variant->size)
            }}
        </span>
    </div>
</div>
