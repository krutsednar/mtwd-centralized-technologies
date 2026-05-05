@props([
    'labels',
    'currentPanel',
    'panels',
    'getHref'
])

<x-filament::dropdown teleport placement="bottom-end" {{ $attributes }}>
   <x-slot name="trigger">
      <button type="button"
              class="flex items-center justify-center w-full p-2 text-sm font-medium rounded-lg shadow-sm outline-none group gap-x-3 bg-primary-500">
                    @if($currentPanel->getId() == 'admin' && auth()->user()?->hasAnyRole(['super_admin',]))
                        <span class="font-semibold bg-white rounded-full text-primary-500">
                            <x-filament::badge
                                icon="fas-user-secret"
                                icon-position="before"
                                >

                                </x-filament::badge>
                        </span>
                        <span class="text-white">
                            Admin
                        </span>
                    @elseif($currentPanel->getId() == 'home')
                        <span class="font-semibold bg-white rounded-full text-primary-500">
                            <x-filament::badge
                                icon="fas-home"
                                icon-position="before"
                                >

                                </x-filament::badge>
                        </span>
                        <span class="text-white">
                            Home
                        </span>
                    @elseif($currentPanel->getId() == 'GSMS')
                        <span class="font-semibold bg-white rounded-full text-primary-500">
                            <x-filament::badge
                            icon="fas-truck-droplet"
                            icon-position="before"
                            >

                            </x-filament::badge>
                        </span>
                        <span class="text-white">
                            General Services Management System
                        </span>
                    @elseif($currentPanel->getId() == 'HRIS')
                        <span class="font-semibold bg-white rounded-full text-primary-500">
                            <x-filament::badge
                            icon="fas-id-card"
                            icon-position="before"
                            >

                            </x-filament::badge>
                        </span>
                        <span class="text-white">
                            Human Resources Information System
                        </span>
                    @endif
         <x-filament::icon
         icon="heroicon-m-chevron-down"
         icon-alias="panels::panel-switch-simple-icon"
         class="w-5 h-5 text-white ms-auto shrink-0"
         />
      </button>
   </x-slot>
   <x-filament::dropdown.list>
      @foreach ($panels as $panel)
         <x-filament::dropdown.list.item
         :href="$getHref($panel)"
         {{-- :badge="str($labels[$panel->getId()] ?? $panel->getId())->substr(0, 2)->upper()" --}}
         tag="a"
         >
         {{-- @if($currentPanel->getId() == 'admin' && auth()->user()?->hasAnyRole(['super_admin',])) --}}
            @if($panel->getId() == 'admin' && auth()->user()?->hasAnyRole(['super_admin',]))
                <x-filament::badge
                    icon="fas-user-secret"
                    icon-position="after"
                >
                    {{ $labels[$panel->getId()] ?? str($panel->getId())->ucfirst() }}
                </x-filament::badge>
            @elseif($panel->getId() == 'home')
                <x-filament::badge
                    icon="fas-home"
                    icon-position="after"
                >
                    {{ $labels[$panel->getId()] ?? str($panel->getId())->ucfirst() }}
                </x-filament::badge>
            @elseif($panel->getId() == 'GSMS')
                <x-filament::badge
                    icon="fas-truck-droplet"
                    icon-position="after"
                >
                    {{ $labels[$panel->getId()] ?? str($panel->getId())->ucfirst() }}
                </x-filament::badge>
            @elseif($panel->getId() == 'HRIS')
                <x-filament::badge
                    icon="fas-id-card"
                    icon-position="after"
                >
                    {{ $labels[$panel->getId()] ?? str($panel->getId())->ucfirst() }}
                </x-filament::badge>
            @endif

            {{-- {{ $labels[$panel->getId()] ?? str($panel->getId())->ucfirst() }} --}}
         </x-filament::dropdown.list.item>
      @endforeach
   </x-filament::dropdown.list>
</x-filament::dropdown>
