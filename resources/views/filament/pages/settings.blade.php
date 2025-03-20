<x-filament-panels::page x-data="{activeTab: $persist('user')}">
    <x-filament::tabs label="Content tabs" class="ml-0">
        <x-filament::tabs.item alpine-active="activeTab === 'user'"
                               x-on:click="activeTab = 'user'">
            Benutzer
        </x-filament::tabs.item>
        <x-filament::tabs.item alpine-active="activeTab === 'billbee'"
                               x-on:click="activeTab = 'billbee'">
            Billbee
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div>
        <div x-show="activeTab == 'user'">
            <livewire:edit-user-settings/>
        </div>
        <div x-show="activeTab == 'billbee'">
            <livewire:edit-billbee-settings/>
        </div>
    </div>
</x-filament-panels::page>
