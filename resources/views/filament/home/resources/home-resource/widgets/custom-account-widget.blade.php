@php
    $user     = filament()->auth()->user();
    $hour     = now()->hour;
    $greeting = match(true) {
        $hour < 12 => 'Good morning',
        $hour < 18 => 'Good afternoon',
        default    => 'Good evening',
    };
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
        {{-- Gradient accent bar --}}
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500"></div>

        <div class="flex items-center gap-4 px-5 py-4">
            {{-- Avatar with glow --}}
            <div class="relative shrink-0">
                <div class="absolute -inset-1 rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 opacity-20 dark:opacity-30 blur"></div>
                <div class="relative rounded-full ring-2 ring-blue-400/40 dark:ring-blue-500/40 ring-offset-2 ring-offset-white dark:ring-offset-gray-900">
                    <x-filament-panels::avatar.user size="lg" :user="$user" />
                </div>
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $greeting }}</p>
                <h2 class="text-base font-bold text-gray-900 dark:text-white truncate mt-0.5">
                    {{ strtoupper($user->name) }}
                </h2>
                <div class="flex flex-wrap items-center gap-2 mt-1.5">
                    @if ($user->division)
                    <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-700/50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:text-blue-300">
                        {{ $user->division->abbreviation }}
                    </span>
                    @endif
                    @if ($user->division?->name)
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user->division->name }}</p>
                    @endif
                </div>
            </div>

            {{-- Logout --}}
            <form action="{{ filament()->getLogoutUrl() }}" method="post" class="shrink-0 my-auto">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 hover:border-red-200 dark:hover:border-red-800/70 hover:bg-red-50 dark:hover:bg-red-950/40 px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-all duration-200">
                    <x-heroicon-m-arrow-left-on-rectangle class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ __('filament-panels::widgets/account-widget.actions.logout.label') }}</span>
                </button>
            </form>
        </div>
    </div>
</x-filament-widgets::widget>
