@php
    use App\Models\Attendance;
    use Carbon\Carbon;
    use Carbon\CarbonPeriod;

    $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $end = $start->copy()->endOfMonth();

    $attendances = $profile
        ? Attendance::where('employee_number', $profile->employee_number)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->attendance_date)->toDateString())
        : collect();

    $days = collect(CarbonPeriod::create($start, $end)->toArray());
@endphp

<div class="py-2">
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
                    @php $record = $attendances->get($day->toDateString()); @endphp
                    <tr class="border-b border-gray-100 dark:border-gray-700 {{ $day->isWeekend() ? 'bg-gray-50/70 dark:bg-gray-700/20' : 'hover:bg-blue-50/40 dark:hover:bg-gray-700/30' }}">
                        <td class="px-3 py-1.5 text-center text-xs font-mono text-gray-500 dark:text-gray-400">{{ $day->day }}</td>
                        <td class="px-3 py-1.5 whitespace-nowrap text-xs font-semibold {{ $day->isWeekend() ? 'text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-200' }}">{{ $day->format('D') }}</td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->morning_in ? 'text-gray-800 dark:text-gray-100' : 'text-gray-300 dark:text-gray-600' }}">{{ $record?->morning_in ? Carbon::parse($record->morning_in)->format('h:i A') : '—' }}</td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->morning_out ? 'text-gray-800 dark:text-gray-100' : 'text-gray-300 dark:text-gray-600' }}">{{ $record?->morning_out ? Carbon::parse($record->morning_out)->format('h:i A') : '—' }}</td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->afternoon_in ? 'text-gray-800 dark:text-gray-100' : 'text-gray-300 dark:text-gray-600' }}">{{ $record?->afternoon_in ? Carbon::parse($record->afternoon_in)->format('h:i A') : '—' }}</td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->afternoon_out ? 'text-gray-800 dark:text-gray-100' : 'text-gray-300 dark:text-gray-600' }}">{{ $record?->afternoon_out ? Carbon::parse($record->afternoon_out)->format('h:i A') : '—' }}</td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->ot_in ? 'text-amber-600 dark:text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">{{ $record?->ot_in ? Carbon::parse($record->ot_in)->format('h:i A') : '—' }}</td>
                        <td class="px-3 py-1.5 text-center font-mono text-xs {{ $record?->ot_out ? 'text-amber-600 dark:text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">{{ $record?->ot_out ? Carbon::parse($record->ot_out)->format('h:i A') : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
