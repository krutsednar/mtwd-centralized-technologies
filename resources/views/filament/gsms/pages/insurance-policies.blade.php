<div>
<x-filament-panels::page>

</x-filament-panels::page>
    <div x-data="{ tab: 'tab1' }">
        <x-filament::tabs label="Content tabs">
            <x-filament::tabs.item
            icon="tabler-backhoe"
            @click="tab = 'tab1'" :alpine-active="'tab === \'tab1\''">
                <b>Heavy Equipment Insurance Policies</b>
            </x-filament::tabs.item>

            <x-filament::tabs.item
            icon="fas-car-side"
            @click="tab = 'tab2'" :alpine-active="'tab === \'tab2\''">
                <b>Vehicle Insurance Policies</b>
            </x-filament::tabs.item>

            <x-filament::tabs.item
            icon="fas-building-flag"
            @click="tab = 'tab3'" :alpine-active="'tab === \'tab3\''">
                <b>Lot/Structure Insurance Policies</b>
            </x-filament::tabs.item>

        </x-filament::tabs>

        <div>
            <div x-show="tab === 'tab1'">
                @livewire('gsms.insurance-policies.heavy-equipment-insurances')
            </div>

            <div x-show="tab === 'tab2'">
                @livewire('gsms.insurance-policies.vehicle-insurances')
            </div>

            <div x-show="tab === 'tab3'">
                @livewire('gsms.insurance-policies.structure-insurances')
            </div>
        </div>
    </div>
</div>
