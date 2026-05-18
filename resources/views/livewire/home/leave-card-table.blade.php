<div class="space-y-5">

    @if (! $profile)
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-950/30 p-8 text-center">
            <div class="mx-auto mb-3 w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-7 h-7 text-amber-600 dark:text-amber-400" />
            </div>
            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">No employee profile is linked to your account.</p>
        </div>
    @else

    {{-- Balance cards --}}
    <div class="grid grid-cols-2 gap-4">
        {{-- VL --}}
        <div class="rounded-2xl border border-emerald-200 dark:border-emerald-800/50 bg-white dark:bg-gray-900 p-5 shadow-sm overflow-hidden relative">
            <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full bg-emerald-500/5 dark:bg-emerald-400/10"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                        <x-heroicon-o-sun class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">VL Balance</p>
                </div>
                <p class="text-3xl font-bold {{ $vlBalance >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-600 dark:text-red-400' }}">
                    {{ number_format($vlBalance, 2) }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Vacation Leave</p>
            </div>
        </div>
        {{-- SL --}}
        <div class="rounded-2xl border border-blue-200 dark:border-blue-800/50 bg-white dark:bg-gray-900 p-5 shadow-sm overflow-hidden relative">
            <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full bg-blue-500/5 dark:bg-blue-400/10"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                        <x-heroicon-o-heart class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    </div>
                    <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">SL Balance</p>
                </div>
                <p class="text-3xl font-bold {{ $slBalance >= 0 ? 'text-blue-700 dark:text-blue-300' : 'text-red-600 dark:text-red-400' }}">
                    {{ number_format($slBalance, 2) }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Sick Leave</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <div>
            <select wire:model.live="category"
                    class="text-sm rounded-xl bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-500 px-3 py-2 outline-none transition-colors w-52">
                <option value="">All Categories</option>
                @foreach ($categories as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select wire:model.live="year"
                    class="text-sm rounded-xl bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-500 px-3 py-2 outline-none transition-colors w-28">
                <option value="">All Years</option>
                @foreach ($years as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm" wire:loading.class="opacity-50 pointer-events-none">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Date Applied</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">VL Earned</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider">VL Used</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">SL Earned</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider">SL Used</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                    @forelse ($entries as $entry)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-5 py-2.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $entry->date_applied?->format('M d, Y') }}
                        </td>
                        <td class="px-5 py-2.5">
                            <span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-700/50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300 whitespace-nowrap">
                                {{ \App\Models\LeaveCard::CATEGORY_SELECT[$entry->category] ?? $entry->category }}
                            </span>
                        </td>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400 text-xs">{{ $entry->period_covered ?: '—' }}</td>
                        <td class="px-5 py-2.5 text-center font-mono text-xs text-gray-500 dark:text-gray-400">
                            {{ $entry->duration && $entry->duration !== '0' ? $entry->duration : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-right font-mono text-xs {{ (float)$entry->vl_earned > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-300 dark:text-gray-700' }}">
                            {{ (float)$entry->vl_earned > 0 ? number_format($entry->vl_earned, 3) : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-right font-mono text-xs {{ (float)$entry->vl_with_pay > 0 ? 'text-red-500 dark:text-red-400' : 'text-gray-300 dark:text-gray-700' }}">
                            {{ (float)$entry->vl_with_pay > 0 ? number_format($entry->vl_with_pay, 3) : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-right font-mono text-xs {{ (float)$entry->sl_earned > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-300 dark:text-gray-700' }}">
                            {{ (float)$entry->sl_earned > 0 ? number_format($entry->sl_earned, 3) : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-right font-mono text-xs {{ (float)$entry->sl_with_pay > 0 ? 'text-red-500 dark:text-red-400' : 'text-gray-300 dark:text-gray-700' }}">
                            {{ (float)$entry->sl_with_pay > 0 ? number_format($entry->sl_with_pay, 3) : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400 text-xs max-w-xs truncate"
                            title="{{ $entry->remarks }}">
                            {{ $entry->remarks ?: '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-2">
                            <x-home.empty-state label="No leave card entries found." icon="heroicon-o-calendar-days" class="border-0 rounded-none shadow-none" />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-1 border-t border-gray-100 dark:border-gray-800 pt-3">{{ $entries->links() }}</div>

    @endif
</div>
