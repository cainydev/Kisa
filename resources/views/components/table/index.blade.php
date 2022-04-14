@props(['columns' => false, 'options' => false])

@php

$template = 'grid-template-columns: auto';

if($columns){
$template = 'grid-template-columns:';
foreach($columns as $col){
$template .= ' '.$col['width'];
}
if($options){
$template .= ' 2fr';
}
}

@endphp


<table {{
       $attributes->merge([
    'class' => 'my-8 grid border-collapse w-full rounded-md overflow-hidden',
    'style' => $template
    ]) }}>

    {{ $slot }}

</table>
