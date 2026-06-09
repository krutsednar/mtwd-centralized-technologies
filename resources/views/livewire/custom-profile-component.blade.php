<div>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="fi-form-actions">
            <div class="flex flex-row-reverse flex-wrap items-center gap-3 fi-ac">
                <x-filament::button type="submit">
                    {{ __('filament-edit-profile::default.save') }}
                </x-filament::button>
            </div>
        </div>
    </x-filament-panels::form>
    <div class="wrapper">
  <header><!-- Local Header --></header>
  <footer><!-- Local Footer --></footer>
</div>
    <x-filament-actions::modals />
</div>
