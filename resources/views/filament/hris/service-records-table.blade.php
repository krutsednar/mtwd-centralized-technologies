<div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                {{-- Header: Using dark:bg-gray-800/50 to keep it distinct from the body --}}
                <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3.5 font-bold text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 whitespace-nowrap">From</th>
                    <th class="px-4 py-3.5 font-bold text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 whitespace-nowrap">To</th>
                    <th class="px-4 py-3.5 font-bold text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 whitespace-nowrap">Agency</th>
                    <th class="px-4 py-3.5 font-bold text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 whitespace-nowrap">Position</th>
                    <th class="px-4 py-3.5 font-bold text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 whitespace-nowrap">Status</th>
                    <th class="px-4 py-3.5 font-bold text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 whitespace-nowrap text-center">SG</th>
                    <th class="px-4 py-3.5 font-bold text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 whitespace-nowrap text-right">Salary</th>
                    <th class="px-4 py-3.5 font-bold text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 whitespace-nowrap text-right">Allowance</th>
                    <th class="px-4 py-3.5 font-bold text-[10px] uppercase tracking-widest text-gray-500 dark:text-gray-400 whitespace-nowrap">Remarks</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($serviceRecords as $sr)
                    <tr class="transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                        {{-- Dates: tabular-nums ensures numbers line up vertically --}}
                        <td class="px-4 py-4 whitespace-nowrap text-gray-600 dark:text-white tabular-nums">
                            {{ $sr->from?->format('M d, Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-600 dark:text-white tabular-nums">
                            {{ $sr->to?->format('M d, Y') ?? '—' }}
                        </td>

                        {{-- Primary Info: Agency uses gray-900/gray-100 for maximum contrast --}}
                        <td class="px-4 py-4">
                            <span class="font-bold text-gray-900 dark:text-gray-100">{{ $sr->agency ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-4 text-gray-600 dark:text-white">
                            {{ $sr->position ?? '—' }}
                        </td>
                        <td class="px-4 py-4 text-gray-600 dark:text-white">
                            {{ $sr->status ?? '—' }}
                        </td>

                        {{-- Salary Grade: Boxed for visibility --}}
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-bold text-xs">
                                {{ $sr->sg ?? '—' }}
                            </span>
                        </td>

                        {{-- Numeric Data --}}
                        <td class="px-4 py-4 text-right font-mono text-gray-900 dark:text-white font-semibold">
                            {{ $sr->salary ? number_format($sr->salary, 2) : '0.00' }}
                        </td>
                        <td class="px-4 py-4 text-right font-mono text-gray-600 dark:text-white">
                            {{ $sr->allowance ? number_format($sr->allowance, 2) : '0.00' }}
                        </td>

                        {{-- Remarks: Lighter gray but still high enough contrast --}}
                        <td class="px-4 py-4">
                            <div class="space-y-1">
                                <p class="text-xs text-gray-500 dark:text-white italic">
                                    {{ $sr->remarks ?? '—' }}
                                </p>
                                @if($sr->other_remarks)
                                    <p class="text-[10px] text-blue-600 dark:text-white font-medium">
                                        {{ $sr->other_remarks }}
                                    </p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500 italic">
                            No service records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
