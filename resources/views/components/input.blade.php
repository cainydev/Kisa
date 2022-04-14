@props(['disabled' => false, 'error' => false])

@php
$border = 'border-gray-300';
if($error) $border = 'border-red-500';

@endphp


<input {{
       $disabled
       ? 'disabled'
       : ''
       }}
       {!!
       $attributes->merge(['class' => $border.' rounded-md shadow-sm focus:border-indigo-300 focus:ring
focus:ring-indigo-200 focus:ring-opacity-50 disabled:text-gray-600 disabled:cursor-not-allowed']) !!}>
