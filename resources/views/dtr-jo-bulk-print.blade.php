<!DOCTYPE html>
<html>
<head>
    <title>JO DTR — {{ strtoupper($division->name) }} — {{ $cutoff === 'first' ? '1st' : '2nd' }} Cut-off — {{ $start->format('F Y') }}</title>
    <style>
        @page {
            size: 8.5in 13in;
            margin: 0.5in 0.5in 0.3in 0.5in;
        }

        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            background: white;
        }

        .employee-block {
            page-break-after: always;
        }

        .employee-block:last-child {
            page-break-after: avoid;
        }

        .container {
            display: flex;
            width: 100%;
            justify-content: space-between;
            position: relative;
        }

        .container::after {
            content: "";
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            border-left: 1px dashed #aaa;
        }

        @media print {
            .container::after { border-left: 1px dashed black; }
            .no-print { display: none !important; }
        }

        .dtr { width: 48%; margin: .3in; }

        h2 {
            text-align: center;
            margin: 0 0 2px;
            font-size: 13px;
            letter-spacing: 1px;
        }

        .sub-header {
            text-align: center;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 10px;
        }

        .info-section { margin-bottom: 6px; }

        .info-row {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 2px;
            padding-bottom: 1px;
        }

        .label { font-weight: bold; width: 90px; flex-shrink: 0; }

        .value { flex-grow: 1; text-transform: uppercase; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid black;
            text-align: center;
            padding: 2px 0;
            height: 17px;
            overflow: hidden;
        }

        th { background-color: #f0f0f0; font-size: 8px; }

        td.time { font-family: monospace; font-size: 8px; }

        .footer { margin-top: 6px; font-size: 9px; }

        .summary-row {
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-family: monospace;
        }

        .certification-text {
            font-size: 8.5px;
            text-align: justify;
            line-height: 1.3;
            margin-top: 5px;
        }

        .signature-block { margin-top: 18px; text-align: center; }

        .signature-line {
            width: 80%;
            margin: 0 auto;
            border-top: 1px solid black;
            padding-top: 2px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .divider {
            text-align: center;
            margin: 5px 0;
            letter-spacing: -1px;
            color: #555;
        }

        .copy-tag { font-weight: bold; font-size: 9px; margin-top: 8px; }

        /* Print controls bar */
        .print-bar {
            background: #92400e;
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .print-bar label { font-size: 12px; }

        .print-bar select {
            padding: 4px 8px;
            border-radius: 4px;
            border: none;
            font-size: 12px;
        }

        .print-bar button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 6px 18px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 4px;
        }

        .print-bar .end {
            background: #af0505;
            color: white;
            border: none;
            padding: 6px 18px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            margin-left: auto;
            text-decoration: none;
            display: inline-block;
        }

        .print-bar button:hover { background: #2563eb; }

        .cutoff-badge {
            background: #d97706;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

{{-- Print Controls Bar --}}
<div class="print-bar no-print">
    <form method="GET" action="{{ route('dtr.print.jo', $division->id) }}" style="display:flex;align-items:center;gap:12px;width:100%">

        <span class="cutoff-badge">JO DTR</span>

        <label>Division:
            <select name="division_id" onchange="this.form.action='{{ url('/dtr/print/jo') }}/'+this.value">
                @foreach(\App\Models\Division::orderBy('name')->get() as $div)
                    <option value="{{ $div->id }}" {{ $div->id == $division->id ? 'selected' : '' }}>
                        {{ $div->name }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>Month:
            <select name="month">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(2000, $m, 1)->format('F') }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>Cut-off:
            <select name="cutoff">
                <option value="first"  {{ $cutoff === 'first'  ? 'selected' : '' }}>1st (26th – 10th)</option>
                <option value="second" {{ $cutoff === 'second' ? 'selected' : '' }}>2nd (11th – 25th)</option>
            </select>
        </label>

        <label>Year:
            <select name="year">
                @foreach(range(now()->year - 5, now()->year + 1) as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit">Load</button>

        <button type="button" onclick="window.print()" style="background:#16a34a;">
            🖨 Print All ({{ $profiles->count() }} JO employees)
        </button>

        <a href="/hris/attendances" class="end">⬅ Back</a>

    </form>
</div>

@php
    $days    = collect(\Carbon\CarbonPeriod::create($start, $end)->toArray());
    $fromStr = $start->format('F d, Y');
    $toStr   = $end->format('F d, Y');
    $copies  = ["EMPLOYEE'S COPY", "PERSONNEL'S COPY"];
@endphp

@forelse ($profiles as $profile)
    @php
        $attendances = $allAttendances->get($profile->employee_number, collect());
    @endphp
    <div class="employee-block">
        <div class="container">
            @foreach ($copies as $copyTag)
            <div class="dtr">
                <h2>DAILY TIME RECORD</h2>
                <p class="sub-header">From: {{ $fromStr }} &nbsp; To: {{ $toStr }}</p>

                <div class="info-section">
                    <div class="info-row">
                        <span class="label">Name:</span>
                        <span class="value">{{ strtoupper($profile->surname) }}, {{ strtoupper($profile->first_name) }} {{ strtoupper($profile->middle_name[0] ?? '') }}.</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Position:</span>
                        <span class="value"></span>
                    </div>
                    <div class="info-row">
                        <span class="label" style="font-size:8px;">Department:</span>
                        <span class="value">{{ strtoupper($division->name) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Regular Time:</span>
                        <span class="value">FLEXY TIME</span>
                        <span class="label" style="margin-left:auto; white-space:nowrap;">Payroll No.:</span>
                        <span class="value">1</span>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" style="width:6%">Date</th>
                            <th rowspan="2" style="width:6%">Day</th>
                            <th colspan="2">AM</th>
                            <th colspan="2">PM</th>
                            <th colspan="2">OT</th>
                        </tr>
                        <tr>
                            <th>In</th><th>Out</th>
                            <th>In</th><th>Out</th>
                            <th>In</th><th>Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($days as $day)
                            @php $record = $attendances->get($day->toDateString()); @endphp
                            <tr>
                                <td>{{ $day->format('d') }}</td>
                                <td>{{ $day->format('D') }}</td>
                                <td class="time">{{ $record?->morning_in ? \Carbon\Carbon::parse($record->morning_in)->format('h:i A') : '' }}</td>
                                <td class="time">{{ $record?->morning_out ? \Carbon\Carbon::parse($record->morning_out)->format('h:i A') : '' }}</td>
                                <td class="time">{{ $record?->afternoon_in ? \Carbon\Carbon::parse($record->afternoon_in)->format('h:i A') : '' }}</td>
                                <td class="time">{{ $record?->afternoon_out ? \Carbon\Carbon::parse($record->afternoon_out)->format('h:i A') : '' }}</td>
                                <td class="time">{{ $record?->ot_in ? \Carbon\Carbon::parse($record->ot_in)->format('h:i A') : '' }}</td>
                                <td class="time">{{ $record?->ot_out ? \Carbon\Carbon::parse($record->ot_out)->format('h:i A') : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="footer">
                    <div class="summary-row">
                        <span>A = {{ $attendances->count() }}</span>
                        <span>ROT = ______</span>
                        <span>LOT = ______</span>
                    </div>
                    <div class="summary-row">
                        <span>U = ______</span>
                        <span>SOT = ______</span>
                    </div>

                    <p class="certification-text">
                        I Certify on my honor that the above is a true and correct report of the hours of work performed,
                        record of which was made daily at the time of arrival and departure from office.
                    </p>

                    <div class="signature-block">
                        <div class="signature-line">Signature Over Printed Name</div>
                    </div>

                    <p class="divider">====================================================</p>
                    <p style="text-align:center; margin:0; font-weight:bold; font-size:9px;">VERIFIED as to the prescribed office hours</p>

                    <div class="signature-block">
                        <div class="signature-line">In-Charge / Supervisor</div>
                    </div>

                    <div class="copy-tag">&gt;&gt;&gt;&gt;&gt; {{ $copyTag }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
@empty
    <div style="text-align:center; padding: 40px; font-size: 14px; color: #666;">
        No Job Order employees found in {{ $division->name }}.
    </div>
@endforelse

</body>
</html>
