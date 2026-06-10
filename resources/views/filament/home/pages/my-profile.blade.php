<x-filament-panels::page>
    @if ($this->profile)
        {{ $this->profileInfolist }}
    @else
        <x-filament::section>
            <div class="flex flex-col items-center gap-2 py-6 text-center">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-8 w-8 text-warning-500" />
                <p class="text-sm font-medium">No employee profile is linked to your account.</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Please contact your HR administrator.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
