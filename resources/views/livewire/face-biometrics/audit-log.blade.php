<div>
    {{-- Daily summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Today</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalToday }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Success Rate</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ $successRate }}%</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Spoof Attempts</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ $spoofCount }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Top Failure Reason</p>
            <p class="text-xl font-semibold text-yellow-600 mt-1">
                {{ $topFailureReasons->keys()->first() ?? 'None' }}
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow mb-4 grid grid-cols-2 md:grid-cols-4 gap-3">
        <select wire:model.live="filterEvent" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <option value="">All Events</option>
            @foreach($events as $ev)
            <option value="{{ $ev }}">{{ $ev }}</option>
            @endforeach
        </select>
        <input type="date" wire:model.live="filterDateFrom"
               class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
               placeholder="From">
        <input type="date" wire:model.live="filterDateTo"
               class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
               placeholder="To">
        <input type="text" wire:model.live="filterProfile" placeholder="Employee name/number"
               class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Time</th>
                    <th class="px-4 py-3 text-left">Event</th>
                    <th class="px-4 py-3 text-left">Employee</th>
                    <th class="px-4 py-3 text-right">Score</th>
                    <th class="px-4 py-3 text-right">Liveness</th>
                    <th class="px-4 py-3 text-right">Quality</th>
                    <th class="px-4 py-3 text-left">Reason</th>
                    <th class="px-4 py-3 text-left">Source</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($log->created_at)->format('M d H:i:s') }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $color = match($log->event) {
                                'verify_ok', 'enroll', 'enroll_seed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                'spoof_detected'                     => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                                'verify_fail', 'duplicate'           => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                default                              => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $color }}">{{ $log->event }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($log->profile)
                            <span class="font-medium text-gray-900 dark:text-white">{{ $log->profile->employee_number }}</span>
                            <span class="text-gray-500 dark:text-gray-400 ml-1 text-xs">{{ $log->profile->full_name }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-xs">{{ $log->match_score ? number_format($log->match_score, 3) : '—' }}</td>
                    <td class="px-4 py-3 text-right font-mono text-xs">{{ $log->liveness_score ? number_format($log->liveness_score, 3) : '—' }}</td>
                    <td class="px-4 py-3 text-right font-mono text-xs">{{ $log->quality_score ? number_format($log->quality_score, 3) : '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $log->reason ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $log->source ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">No audit logs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $logs->links() }}
        </div>
    </div>
</div>
