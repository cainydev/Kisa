@php use App\Filament\Widgets\{StatusGeneral, StatusVersions, StatusLoad}; @endphp

<x-filament-panels::page>
    @livewire(StatusVersions::class)
    @livewire(StatusGeneral::class)
    @livewire(StatusLoad::class)
</x-filament-panels::page>
