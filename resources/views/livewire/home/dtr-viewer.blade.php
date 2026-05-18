<div class="space-y-5">

    @if (! $profile)
        <div class="rounded-2xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-950/30 p-8 text-center">
            <div class="mx-auto mb-3 w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-7 h-7 text-amber-600 dark:text-amber-400" />
            </div>
            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">No employee profile is linked to your account.</p>
            <p class="text-xs text-amber-500 mt-1">Please contact your HR administrator.</p>
        </div>
    @else

    {{-- Period selector --}}
    <div class="flex flex-wrap items-end gap-4" wire:loading.class="opacity-40 pointer-events-none">
        <div>
            <label class="block mb-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Month</label>
            <select wire:model.live="month"
                    class="text-sm rounded-xl bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-500 px-3 py-2 outline-none transition-colors w-44">
                @foreach ($months as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block mb-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Year</label>
            <select wire:model.live="year"
                    class="text-sm rounded-xl bg-white dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-500 px-3 py-2 outline-none transition-colors w-28">
                @foreach ($years as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="hidden sm:flex items-center gap-1.5 ml-auto text-xs text-gray-400 dark:text-gray-500 font-medium self-end pb-2.5">
            <x-heroicon-o-user-circle class="w-4 h-4 shrink-0" />
            {{ $profile->full_name }} &mdash;
            <span class="font-mono">{{ $profile->employee_number }}</span>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $daysPresent = $attendances->whereNotNull('morning_in')->count();
        $daysWithOt  = $attendances->whereNotNull('ot_in')->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/10 to-cyan-500/10 dark:from-blue-500/20 dark:to-cyan-500/20 flex items-center justify-center shrink-0">
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-blue-500 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $daysPresent }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Days Present</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500/10 to-orange-500/10 dark:from-amber-500/20 dark:to-orange-500/20 flex items-center justify-center shrink-0">
                    <x-heroicon-o-clock class="w-5 h-5 text-amber-500 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $daysWithOt }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Days with OT</p>
                </div>
            </div>
        </div>
    </div>

    {{-- DTR table --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm" wire:loading.class="opacity-50">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Day</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">AM In</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">AM Out</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">PM In</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">PM Out</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-amber-500 dark:text-amber-400 uppercase tracking-wider whitespace-nowrap">OT In</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-amber-500 dark:text-amber-400 uppercase tracking-wider whitespace-nowrap">OT Out</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800/60">
                    @foreach ($days as $day)
                        @php $record = $attendances->get($day->toDateString()); @endphp
                        <tr title="{{ $day->isWeekend() ? 'Weekend' : '' }}"
                            class="{{ $day->isWeekend()
                                ? 'bg-gray-50/80 dark:bg-gray-800/20'
                                : 'hover:bg-gray-50/60 dark:hover:bg-gray-800/30' }} transition-colors">
                            <td class="px-3 py-1.5 text-center text-xs font-mono text-gray-400 dark:text-gray-500">{{ $day->day }}</td>
                            <td class="px-3 py-1.5 text-center text-xs font-semibold
                                       {{ $day->isWeekend() ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $day->format('D') }}
                            </td>
                            <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->morning_in ? 'text-gray-700 dark:text-gray-200' : 'text-gray-200 dark:text-gray-700' }}">
                                {{ $record?->morning_in ? \Carbon\Carbon::parse($record->morning_in)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->morning_out ? 'text-gray-700 dark:text-gray-200' : 'text-gray-200 dark:text-gray-700' }}">
                                {{ $record?->morning_out ? \Carbon\Carbon::parse($record->morning_out)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->afternoon_in ? 'text-gray-700 dark:text-gray-200' : 'text-gray-200 dark:text-gray-700' }}">
                                {{ $record?->afternoon_in ? \Carbon\Carbon::parse($record->afternoon_in)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->afternoon_out ? 'text-gray-700 dark:text-gray-200' : 'text-gray-200 dark:text-gray-700' }}">
                                {{ $record?->afternoon_out ? \Carbon\Carbon::parse($record->afternoon_out)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->ot_in ? 'text-amber-600 dark:text-amber-400' : 'text-gray-200 dark:text-gray-700' }}">
                                {{ $record?->ot_in ? \Carbon\Carbon::parse($record->ot_in)->format('h:i A') : '—' }}
                            </td>
                            <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->ot_out ? 'text-amber-600 dark:text-amber-400' : 'text-gray-200 dark:text-gray-700' }}">
                                {{ $record?->ot_out ? \Carbon\Carbon::parse($record->ot_out)->format('h:i A') : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @endif
</div>
