<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Record — {{ $profile->full_name }}</title>
    <style>
        /*
         * HOW THIS WORKS
         * ──────────────
         * @page reserves a 160px top margin and 60px bottom margin on every
         * printed page. Content only flows inside that inner area — it can
         * never reach the margins.
         *
         * .page-header uses position:fixed with top:-160px so it sits exactly
         * inside the top margin zone and is stamped on every page.
         *
         * .page-footer uses position:fixed with bottom:-60px so it sits exactly
         * inside the bottom margin zone on every page.
         *
         * IMPORTANT: CSS custom properties (var()) do NOT work inside @page
         * rules, so the pixel values are hardcoded. If you change the header
         * or footer height, update ALL THREE places:
         *   1. @page  margin-top / margin-bottom
         *   2. .page-header  top / height
         *   3. .page-footer  bottom / height
         *
         * No JavaScript needed — pure CSS.
         */

        @page {
            size: 8.5in 13in;
            margin: 160px 0.5in 60px 0.5in;
        }

        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11.5px;
            margin: 0;
            padding: 0;
            background: white;
            color: #000;
        }

        /* ── Fixed Page Header — stamped on every page in the top margin zone ── */
        .page-header {
            position: fixed;
            top: -160px;
            left: 0;
            right: 0;
            height: 160px;
            background: white;
            padding: 0.2in 0.5in 6px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .header {
            display: grid;
            grid-template-columns: 25% 50% 25%;
            align-items: center;
            width: 100%;
        }

        .header .logo-left  { text-align: right; }
        .header .logo-right { text-align: left;  }

        .header img.logo {
            height: 70px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
        }

        .header-center {
            text-align: center;
            line-height: 1.4;
        }

        .header-center .republic    { font-size: 12px; margin-bottom: 1px; }
        .header-center .org-name    { font-size: 13.5px; font-weight: bold; letter-spacing: 0.2px; }
        .header-center .org-details { font-size: 10px; color: #111; }

        .separator { margin: 5px 0 0; }

        .sep-blue {
            height: 2px;
            background: #003399 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            margin-bottom: 3px;
        }

        .sep-red {
            height: 4px;
            background: #cc0000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .doc-title {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            margin: 8px 0 0;
            letter-spacing: 0.5px;
        }

        /* ── Fixed Page Footer — stamped on every page in the bottom margin zone ── */
        .page-footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 60px;
            background: white;
            text-align: center;
            line-height: 0;
        }

        .page-footer img {
            width: 100%;
            height: 80px;
            object-fit: contain;
            object-position: center;
            display: block;
        }

        /* ── Main content flows inside the @page content area ── */
        .content-wrapper {
            padding: 10px 0 0.3in;
        }

        /* Prevent table rows from splitting across pages */
        tbody tr {
            page-break-inside: avoid;
            break-inside: avoid;
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

        /* ── Document closing section ── */
        .footer-text {
            font-size: 10px;
            margin-top: 12px;
            line-height: 1.5;
        }

        .certified-block {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            width: 100%;
        }

        .certified-label {
            font-size: 11.5px;
            font-weight: bold;
            padding-bottom: 15px;
        }

        .signature-block {
            text-align: center;
            font-size: 11px;
            width: 240px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 100%;
            margin: 0 auto 2px;
            padding-top: 4px;
            text-align: center;
        }

        .legend {
            margin-top: 15px;
            font-size: 10px;
            line-height: 1.5;
        }

        /* ── Print overrides ── */
        @media print {
            .no-print { display: none !important; }
        }

        /* ── Screen print button ── */
        .no-print {
            text-align: center;
            padding: 14px 0;
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
        }

        .no-print button {
            padding: 9px 28px;
            font-size: 13px;
            cursor: pointer;
            background: #1d4ed8;
            color: white;
            border: none;
            border-radius: 6px;
            font-family: Arial, sans-serif;
        }

        .no-print button:hover { background: #1e40af; }
    </style>
</head>
<body>

{{-- ── Screen Print Button ── --}}
<div class="no-print">
    <button onclick="window.print()">🖨&nbsp; Print Service Record</button>
</div>

{{-- ── Fixed Header — appears on every page in the top margin zone ── --}}
<div class="page-header">
    <div class="header">
        <div class="logo-left">
            <img class="logo" src="{{ asset('images/MTWD-Logo.png') }}" alt="MTWD Logo">
        </div>
        <div class="header-center">
            <div class="republic">Republic of the Philippines</div>
            <div class="org-name">METROPOLITAN TUGUEGARAO WATER DISTRICT</div>
            <div class="org-details">Main Avenue, San Gabriel, Tuguegarao City</div>
            <div class="org-details">Tel. No. (078) 844-1586; 844-7309; Telefax: (078) 844-9136</div>
            <div class="org-details">Website: www.mtwd.gov.ph</div>
        </div>
        <div class="logo-right">
            <img class="logo" src="{{ asset('images/Bagong-Pilipinas-Logo.png') }}" alt="Bagong Pilipinas">
        </div>
    </div>
    <div class="separator">
        <div class="sep-blue"></div>
        <div class="sep-red"></div>
    </div>
    <div class="doc-title">SERVICE RECORD</div>
</div>

{{-- ── Fixed Footer — appears on every page in the bottom margin zone ── --}}
<div class="page-footer">
    <img src="{{ asset('images/footer.png') }}" alt="Footer">
</div>

{{-- ── Main Content — flows inside @page content area only ── --}}
<div class="content-wrapper">

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

    <div style="text-align:right; padding-right:100px; padding-top:20px;">
        CERTIFIED CORRECT:
    </div>

    <div class="certified-block">
        <div class="certified-label">
            <span style="text-decoration:underline;">{{ now()->format('F d, Y') }}</span><br>Date
        </div>
        <div class="signature-block">
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
        <span style="margin-left:32px;">D &ndash; Daily</span>
        <br><span style="margin-left:100px;">C &ndash; Casual</span>
        <span style="margin-left:50%;">M &ndash; Monthly</span>
        <br><span style="margin-left:100px;">T &ndash; Temporary</span>
    </div>

</div>

</body>
</html>
