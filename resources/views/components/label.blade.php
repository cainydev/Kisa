@props(['value'])

<label {{
       $attributes->merge(['class' => 'block ml-px font-medium text-sm text-gray-700']) }}>
    {{ $value ?? $slot }}
</label>
