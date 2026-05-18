<div class="space-y-5">

    @if (! $profile)
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-950/30 p-8 text-center">
            <div class="mx-auto mb-3 w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-7 h-7 text-amber-600 dark:text-amber-400" />
            </div>
            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">No employee profile is linked to your account.</p>
        </div>
    @elseif ($serviceRecords->isEmpty())
        <x-home.empty-state label="No service records on file." icon="heroicon-o-clipboard-document-list" />
    @else

    {{-- Summary stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/10 to-cyan-500/10 dark:from-blue-500/20 dark:to-cyan-500/20 flex items-center justify-center shrink-0">
                    <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-blue-500 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $serviceRecords->count() }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Entries</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500/10 to-teal-500/10 dark:from-emerald-500/20 dark:to-teal-500/20 flex items-center justify-center shrink-0">
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-emerald-500 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalYears }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Service</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">From</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">To</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Agency</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Position</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">SG</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Salary</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Allowance</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-900">
                    @foreach ($serviceRecords as $sr)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-5 py-2.5 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $sr->from ? \Carbon\Carbon::parse($sr->from)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-5 py-2.5 whitespace-nowrap">
                            @if ($sr->to)
                                <span class="text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($sr->to)->format('M d, Y') }}</span>
                            @else
                                <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-700/50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">Present</span>
                            @endif
                        </td>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400">{{ $sr->agency ?: '—' }}</td>
                        <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $sr->position ?: '—' }}</td>
                        <td class="px-5 py-2.5">
                            @if ($sr->status)
                                <span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-700/50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">{{ $sr->status }}</span>
                            @else
                                <span class="text-gray-300 dark:text-gray-700">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-2.5 text-center text-gray-500 dark:text-gray-400">{{ $sr->sg ?: '—' }}</td>
                        <td class="px-5 py-2.5 text-right font-mono text-gray-500 dark:text-gray-400">
                            {{ $sr->salary ? '₱'.number_format($sr->salary, 2) : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-right font-mono text-gray-500 dark:text-gray-400">
                            {{ $sr->allowance ? '₱'.number_format($sr->allowance, 2) : '—' }}
                        </td>
                        <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400 text-xs max-w-xs truncate"
                            title="{{ $sr->remarks }}">
                            {{ $sr->remarks ?: '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @endif
</div>
