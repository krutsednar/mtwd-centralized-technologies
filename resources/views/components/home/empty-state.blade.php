@props(['label', 'icon' => 'heroicon-o-inbox'])

<div {{ $attributes->class(['rounded-2xl border border-gray-200 dark:border-gray-800 p-12 text-center']) }}>
    <div class="mx-auto mb-4 w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500/10 to-cyan-500/10 dark:from-blue-500/20 dark:to-cyan-500/20 border border-blue-200/50 dark:border-blue-500/20 flex items-center justify-center">
        @svg($icon, 'w-7 h-7 text-blue-500 dark:text-blue-400')
    </div>
    <p class="text-sm text-gray-400 dark:text-gray-500 italic">{{ $label }}</p>
</div>
