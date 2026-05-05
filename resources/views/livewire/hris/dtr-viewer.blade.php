<div class="space-y-4">
    {{-- Period Selector --}}
    <div class="flex items-center justify-center gap-3 px-1 flex-wrap">
        <div class="w-44">
            <label class="block mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Month</label>
            <select wire:model.live="month"
                class="w-full text-sm rounded-lg
                       bg-white dark:bg-gray-700
                       text-gray-900 dark:text-gray-100
                       border border-gray-300 dark:border-gray-600
                       focus:ring-primary-500 focus:border-primary-500">
                @foreach ($months as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-28">
            <label class="block mb-1 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Year</label>
            <select wire:model.live="year"
                class="w-full text-sm rounded-lg
                       bg-white dark:bg-gray-700
                       text-gray-900 dark:text-gray-100
                       border border-gray-300 dark:border-gray-600
                       focus:ring-primary-500 focus:border-primary-500">
                @foreach ($years as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="self-end">
            <button type="button"
                    x-on:click="$dispatch('close-modal', { id: 'view_dtr' })"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors border border-gray-300 dark:border-gray-600">
                ← Back
            </button>
        </div>
    </div>

    {{-- DTR Table --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-600">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700">
                    <th class="px-3 py-2 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap text-center">#</th>
                    <th class="px-3 py-2 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap text-center">Day</th>
                    <th class="px-3 py-2 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap text-center">AM In</th>
                    <th class="px-3 py-2 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap text-center">AM Out</th>
                    <th class="px-3 py-2 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap text-center">PM In</th>
                    <th class="px-3 py-2 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap text-center">PM Out</th>
                    <th class="px-3 py-2 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap text-center">OT In</th>
                    <th class="px-3 py-2 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap text-center">OT Out</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800">
                @foreach ($days as $day)
                    @php
                        $record = $attendances->get($day->toDateString());
                    @endphp
                    <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-blue-50/40 dark:hover:bg-gray-700/30">
                        <td class="px-3 py-1.5 text-center text-xs font-mono text-gray-500 dark:text-gray-400">
                            {{ $day->day }}
                        </td>
                        <td class="px-3 py-1.5 whitespace-nowrap text-xs font-semibold text-gray-800 dark:text-gray-200">
                            {{ $day->format('D') }}
                        </td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->morning_in ? 'text-gray-800 dark:text-gray-100' : 'text-gray-300 dark:text-gray-600' }}">
                            {{ $record?->morning_in ? \Carbon\Carbon::parse($record->morning_in)->format('h:i A') : '—' }}
                        </td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->morning_out ? 'text-gray-800 dark:text-gray-100' : 'text-gray-300 dark:text-gray-600' }}">
                            {{ $record?->morning_out ? \Carbon\Carbon::parse($record->morning_out)->format('h:i A') : '—' }}
                        </td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->afternoon_in ? 'text-gray-800 dark:text-gray-100' : 'text-gray-300 dark:text-gray-600' }}">
                            {{ $record?->afternoon_in ? \Carbon\Carbon::parse($record->afternoon_in)->format('h:i A') : '—' }}
                        </td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->afternoon_out ? 'text-gray-800 dark:text-gray-100' : 'text-gray-300 dark:text-gray-600' }}">
                            {{ $record?->afternoon_out ? \Carbon\Carbon::parse($record->afternoon_out)->format('h:i A') : '—' }}
                        </td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->ot_in ? 'text-amber-600 dark:text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">
                            {{ $record?->ot_in ? \Carbon\Carbon::parse($record->ot_in)->format('h:i A') : '—' }}
                        </td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->ot_out ? 'text-amber-600 dark:text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">
                            {{ $record?->ot_out ? \Carbon\Carbon::parse($record->ot_out)->format('h:i A') : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
