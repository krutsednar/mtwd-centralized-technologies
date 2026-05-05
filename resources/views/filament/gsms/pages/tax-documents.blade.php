<div>
<x-filament-panels::page>

</x-filament-panels::page>
    <div x-data="{ tab: 'tab1' }">
        <x-filament::tabs label="Content tabs">
            <x-filament::tabs.item
            icon="tabler-receipt-tax"
            @click="tab = 'tab1'" :alpine-active="'tab === \'tab1\''">
                <b>Real Property Taxes</b>
            </x-filament::tabs.item>

            <x-filament::tabs.item
            icon="tabler-receipt-tax"
            @click="tab = 'tab2'" :alpine-active="'tab === \'tab2\''">
                <b>Tax Declarations</b>
            </x-filament::tabs.item>

        </x-filament::tabs>

        <div>
            <div x-show="tab === 'tab1'">
                @livewire('gsms.tax-documents.real-property-taxes')
            </div>

            <div x-show="tab === 'tab2'">
                @livewire('gsms.tax-documents.tax-declarations')
            </div>
        </div>
    </div>
</div>
