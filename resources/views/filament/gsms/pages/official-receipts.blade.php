<div>
<x-filament-panels::page>

</x-filament-panels::page>
    <div x-data="{ tab: 'tab1' }">
        <x-filament::tabs label="Content tabs">
            <x-filament::tabs.item
            icon="tabler-backhoe"
            @click="tab = 'tab1'" :alpine-active="'tab === \'tab1\''">
                <b>Heavy Equipment Official Receipts</b>
            </x-filament::tabs.item>

            <x-filament::tabs.item
            icon="fas-car-side"
            @click="tab = 'tab2'" :alpine-active="'tab === \'tab2\''">
                <b>Vehicle Official Receipts</b>
            </x-filament::tabs.item>

        </x-filament::tabs>

        <div>
            <div x-show="tab === 'tab1'">
                @livewire('gsms.official-receipts.heavy-equipment-official-receipts')
            </div>

            <div x-show="tab === 'tab2'">
                @livewire('gsms.official-receipts.vehicle-official-receipts')
            </div>
        </div>
    </div>
</div>
