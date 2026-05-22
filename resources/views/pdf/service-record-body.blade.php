<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Record</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11.5px;
            margin: 0;
            padding: 0;
            color: #000;
            background: white;
        }

        /* ── Employee Info ── */
        .info {
            margin-bottom: 12px;
            font-size: 12.5px;
            line-height: 1.6;
        }

        .info p { margin: 3px 0; }

        .certify-text {
            margin-top: 10px;
            font-size: 12px;
            line-height: 1.5;
            text-align: justify;
        }

        /* ── Service Record Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 4px;
        }

        table th, table td {
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: center;
            vertical-align: middle;
        }

        table th {
            background-color: #fff;
            font-weight: bold;
            font-size: 10px;
            line-height: 1.3;
        }

        td.left  { text-align: left; }
        td.right { text-align: right; }

        /* Prevent rows from splitting across pages */
        tr { page-break-inside: avoid; }

        /* ── Document closing section ── */
        .footer-text {
            font-size: 10px;
            margin-top: 12px;
            line-height: 1.5;
        }

        .certified-block {
            margin-top: 25px;
            display: table;
            width: 100%;
        }

        .certified-left {
            display: table-cell;
            font-size: 11.5px;
            font-weight: bold;
            vertical-align: bottom;
            padding-bottom: 15px;
        }

        .certified-right {
            display: table-cell;
            text-align: center;
            width: 240px;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-size: 11px;
            text-align: center;
        }

        .legend {
            margin-top: 15px;
            font-size: 10px;
            line-height: 1.5;
        }
    </style>
</head>
<body>

@php
    $middleInitial = $profile->middle_name ? strtoupper(substr($profile->middle_name, 0, 1)) . '.' : '';
    $displayName   = strtoupper($profile->surname) . ', ' . strtoupper($profile->first_name) . ' ' . $middleInitial;
    $birthDate     = $profile->date_of_birth
        ? \Carbon\Carbon::parse($profile->date_of_birth)->format('F d, Y')
        : '';
    $birthPlace    = strtoupper($profile->place_of_birth ?? '');
@endphp

<div class="info">
    <p>Name: <strong>{{ $displayName }}</strong></p>
    <p>Birth: <strong>{{ $birthDate }}{{ $birthDate && $birthPlace ? ', ' : '' }}{{ $birthPlace }}</strong></p>
    <p class="certify-text">
        This is to certify that the employee named herein above actually render services in this Office as shown by
        the service record below, each line of which is supported by appointment and other papers actually issued by
        this Office as approved by the authorities concerned:
    </p>
</div>

<table>
    <thead>
        <tr>
            <th rowspan="2" colspan="2">SERVICE</th>
            <th rowspan="2" colspan="4">RECORD OF APPOINTMENT</th>
            <th rowspan="4">ALLOWANCE</th>
            <th rowspan="4">CODE<br>(1)</th>
            <th rowspan="4">AGENCY</th>
            <th rowspan="4">REMARKS</th>
        </tr>
        <tr></tr>
        <tr>
            <th colspan="2">(INCLUSIVE DATE)</th>
            <th rowspan="2">POSITION</th>
            <th rowspan="2">STATUS</th>
            <th rowspan="2" style="width:35px;">SG</th>
            <th rowspan="2">SALARY</th>
        </tr>
        <tr>
            <th style="width:55px;">FROM</th>
            <th style="width:55px;">TO</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($serviceRecords as $index => $sr)
        @php
            $prev         = $index > 0 ? $serviceRecords[$index - 1] : null;
            $showPosition = ! $prev || trim($sr->position) !== trim($prev->position);
            $showAgency   = ! $prev || trim($sr->agency)   !== trim($prev->agency);
            $sgStep       = $sr->sg . ($sr->increment ? '-' . $sr->increment : '');
        @endphp
        <tr>
            <td>{{ $sr->from ? $sr->from->format('m/d/y') : '' }}</td>
            <td>{{ $sr->to   ? $sr->to->format('m/d/y')   : '' }}</td>
            <td class="left">{{ $showPosition ? $sr->position : '-do-' }}</td>
            <td>{{ $sr->status }}</td>
            <td>{{ $sgStep }}</td>
            <td class="right">{{ $sr->salary    ? number_format($sr->salary, 2)    : '' }}</td>
            <td class="right">{{ $sr->allowance ? number_format($sr->allowance, 2) : '' }}</td>
            <td>{{ $sr->code }}</td>
            <td class="left">{{ $showAgency ? $sr->agency : '-do-' }}</td>
            <td class="left">{{ trim(($sr->remarks ?? '') . ' ' . ($sr->other_remarks ?? '')) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="10" style="text-align:center; padding:18px; font-style:italic; color:#555;">
                NO DATA FOUND
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer-text">
    Issued in compliance with Executive Order No. 54 dated August 10, 1954, and in accordance with Circular No. 58
    dated August 10, 1954 of the System.
</div>

<div style="text-align:right; padding-right:100px; padding-top:20px; font-size:11.5px;">
    CERTIFIED CORRECT:
</div>

<div class="certified-block">
    <div class="certified-left">
        <span style="text-decoration:underline;">{{ now()->format('F d, Y') }}</span><br>Date
    </div>
    <div class="certified-right">
        <div class="signature-line">
            <b>MA. TERESITA P. CRUZ</b><br>
            Division Manager A - Human Resource Division
        </div>
    </div>
</div>

<div class="legend">
    <strong>Legend: Status</strong>
    <span style="margin-left:26px;">P &ndash; Permanent</span>
    <span style="margin-left:40%;"><strong>Code (1)</strong></span>
    <span style="margin-left:29px;">D &ndash; Daily</span>
    <br><span style="margin-left:100px;">C &ndash; Casual</span>
    <span style="margin-left:51%;">M &ndash; Monthly</span>
    <br><span style="margin-left:100px;">T &ndash; Temporary</span>
</div>

</body>
</html>
