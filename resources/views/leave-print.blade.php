<!DOCTYPE html>
<html>
<head>
    <title>Application for Leave — {{ $leaveApplication->profile?->full_name }}</title>
    <style>
        @page { size: 8.5in 11in; margin: 0.5in; }

        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
            background: white;
            color: #000;
        }

        h2 {
            text-align: center;
            font-size: 13px;
            letter-spacing: 1px;
            margin: 0 0 2px;
        }

        .sub-header {
            text-align: center;
            font-size: 8px;
            margin-bottom: 10px;
            color: #444;
        }

        .form-row {
            display: flex;
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
            padding-bottom: 2px;
            align-items: flex-end;
            gap: 8px;
        }

        .field-label {
            font-size: 7.5px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .field-value {
            flex-grow: 1;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            min-height: 14px;
        }

        .section-title {
            font-weight: bold;
            font-size: 8px;
            background: #e5e5e5;
            padding: 2px 4px;
            margin: 6px 0 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .grid-6 { display: grid; grid-template-columns: repeat(6, 1fr); gap: 4px; }

        .check-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 4px 0;
            font-size: 9px;
        }

        .check-item { display: flex; align-items: center; gap: 4px; }
        .check-box {
            width: 10px; height: 10px;
            border: 1px solid #000;
            display: inline-block;
            text-align: center;
            line-height: 10px;
            font-size: 8px;
        }

        .credits-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .credits-table th, .credits-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: center;
        }

        .credits-table th { background: #f0f0f0; font-size: 7.5px; }

        .signature-block { margin-top: 14px; }
        .sig-line {
            border-top: 1px solid #000;
            padding-top: 2px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            width: 70%;
            margin: 0 auto;
        }

        .sig-label {
            text-align: center;
            font-size: 7.5px;
            color: #555;
            margin-top: 1px;
        }

        .footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px; }

        .medical-notice {
            font-size: 7.5px;
            font-style: italic;
            color: #555;
            margin-top: 4px;
        }

        /* Print controls bar */
        .print-bar {
            background: #1e293b;
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .print-bar button {
            background: #16a34a;
            color: white;
            border: none;
            padding: 6px 18px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
        }

        .print-bar a.back {
            background: #af0505;
            color: white;
            text-decoration: none;
            padding: 6px 18px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            margin-left: auto;
        }

        @media print { .print-bar { display: none !important; } }
    </style>
</head>
<body>

<div class="print-bar">
    <span style="font-size:13px;font-weight:bold;">Application for Leave — {{ $leaveApplication->profile?->full_name }}</span>
    <button type="button" onclick="window.print()">🖨 Print</button>
    <a href="/hris/leave-applications" class="back">⬅ Back</a>
</div>

@php
    $la       = $leaveApplication;
    $profile  = $la->profile;
    $credits  = $la->certification_leave_credits ?? [];
    $checked  = fn(bool $cond): string => $cond ? '✓' : '';
@endphp

<h2>APPLICATION FOR LEAVE</h2>
<p class="sub-header">Civil Service Form No. 6 | Revised 2020</p>

{{-- Header Info --}}
<div class="grid-3" style="gap:12px;margin-bottom:6px;">
    <div>
        <div class="field-label">1. Office/Department</div>
        <div class="field-value">{{ strtoupper($profile?->division?->name ?? '') }}</div>
    </div>
    <div>
        <div class="field-label">2. Name (Last, First, MI)</div>
        <div class="field-value">
            {{ strtoupper($profile?->surname ?? '') }},
            {{ strtoupper($profile?->first_name ?? '') }}
            {{ strtoupper(substr($profile?->middle_name ?? '', 0, 1)) }}.
        </div>
    </div>
    <div>
        <div class="field-label">3. Date of Filing</div>
        <div class="field-value">{{ $la->date_of_filing?->format('F d, Y') }}</div>
    </div>
    <div>
        <div class="field-label">4. Position</div>
        <div class="field-value">{{ strtoupper($la->position ?? '') }}</div>
    </div>
    <div>
        <div class="field-label">5. Salary</div>
        <div class="field-value">₱ {{ number_format($la->salary ?? 0, 2) }}</div>
    </div>
</div>

{{-- 6. Details --}}
<div class="section-title">6. Details of Application</div>

<div class="grid-2" style="gap:12px;">
    {{-- Left: Type of Leave --}}
    <div>
        <div class="field-label" style="margin-bottom:3px;">Type of Leave</div>
        @foreach(\App\Models\LeaveApplication::LEAVE_TYPE_SELECT as $key => $label)
        <div class="check-item" style="margin-bottom:2px;">
            <span class="check-box">{{ $checked($la->leave_type === $key) }}</span>
            <span>{{ $label }}</span>
        </div>
        @endforeach
    </div>

    {{-- Right: Details --}}
    <div>
        <div class="field-label" style="margin-bottom:3px;">6.A — Vacation / Special Privilege Leave</div>
        <div class="check-row" style="margin-bottom:4px;">
            <div class="check-item"><span class="check-box">{{ $checked($la->details_location === 'within_philippines') }}</span> Within the Philippines</div>
            <div class="check-item"><span class="check-box">{{ $checked($la->details_location === 'abroad') }}</span> Abroad</div>
        </div>
        <div style="font-size:8px;">(specify) {{ $la->details_location_specific }}</div>

        <div class="field-label" style="margin-top:6px;margin-bottom:3px;">6.B — Sick Leave</div>
        <div class="check-row" style="margin-bottom:2px;">
            <div class="check-item"><span class="check-box">{{ $checked($la->details_sick_leave === 'in_hospital') }}</span> In Hospital</div>
            <div class="check-item"><span class="check-box">{{ $checked($la->details_sick_leave === 'out_patient') }}</span> Out Patient</div>
        </div>
        <div style="font-size:8px;">(illness) {{ $la->details_sick_leave_specific }}</div>
        @if($la->details_special_benefits_women)
        <div style="font-size:8px;margin-top:3px;">(Women) {{ $la->details_special_benefits_women }}</div>
        @endif

        <div class="field-label" style="margin-top:6px;margin-bottom:3px;">Study / Other Purpose</div>
        @foreach(\App\Models\LeaveApplication::STUDY_LEAVE_SELECT as $key => $label)
        <div class="check-item" style="margin-bottom:1px;">
            <span class="check-box">{{ $checked($la->details_study_leave === $key) }}</span> {{ $label }}
        </div>
        @endforeach
        @foreach(\App\Models\LeaveApplication::OTHER_PURPOSE_SELECT as $key => $label)
        <div class="check-item" style="margin-bottom:1px;">
            <span class="check-box">{{ $checked($la->details_other_purpose === $key) }}</span> {{ $label }}
        </div>
        @endforeach
    </div>
</div>

<div class="grid-3" style="gap:12px;margin-top:6px;">
    <div>
        <div class="field-label">6.C — Number of Working Days</div>
        <div class="field-value" style="font-size:12px;font-weight:bold;text-align:center;">{{ $la->days_applied_number }}</div>
    </div>
    <div>
        <div class="field-label">Inclusive Dates: From</div>
        <div class="field-value">{{ $la->from?->format('F d, Y') }}</div>
    </div>
    <div>
        <div class="field-label">To</div>
        <div class="field-value">{{ $la->to?->format('F d, Y') }}</div>
    </div>
</div>

<div style="margin-top:6px;">
    <div class="field-label">6.D — Commutation</div>
    <div class="check-row">
        <div class="check-item">
            <span class="check-box">{{ $checked($la->commutation === 'requested') }}</span> Requested
        </div>
        <div class="check-item">
            <span class="check-box">{{ $checked($la->commutation === 'not_requested') }}</span> Not Requested
        </div>
    </div>
</div>

@if($la->requires_medical_certificate)
<p class="medical-notice">* Medical certificate required (Sick Leave exceeds 5 days)</p>
@endif

<div class="signature-block">
    <div class="sig-line">{{ strtoupper($profile?->full_name ?? '') }}</div>
    <div class="sig-label">Signature of Applicant</div>
</div>

{{-- 7. Action on Application --}}
<div class="section-title" style="margin-top:12px;">7. Details of Action on Application</div>

<div class="grid-2" style="gap:16px;">
    {{-- 7.A Leave Credits --}}
    <div>
        <div class="field-label" style="margin-bottom:4px;">7.A — Certification of Leave Credits</div>
        <table class="credits-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Vacation Leave</th>
                    <th>Sick Leave</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align:left;">Total Earned</td>
                    <td>{{ $credits['vacation_earned'] ?? '' }}</td>
                    <td>{{ $credits['sick_earned'] ?? '' }}</td>
                </tr>
                <tr>
                    <td style="text-align:left;">Less This Application</td>
                    <td>{{ $credits['less_vacation'] ?? '' }}</td>
                    <td>{{ $credits['less_sick'] ?? '' }}</td>
                </tr>
                <tr>
                    <td style="text-align:left;font-weight:bold;">Balance</td>
                    <td><strong>{{ $credits['balance_vacation'] ?? '' }}</strong></td>
                    <td><strong>{{ $credits['balance_sick'] ?? '' }}</strong></td>
                </tr>
            </tbody>
        </table>
        <div class="signature-block" style="margin-top:12px;">
            <div class="sig-line">{{ strtoupper($la->authorized_officer_certification ?? '') }}</div>
            <div class="sig-label">Authorized Officer</div>
        </div>
    </div>

    {{-- 7.B Recommendation + 7.C/D Approval --}}
    <div>
        <div class="field-label" style="margin-bottom:3px;">7.B — Recommendation</div>
        <div class="check-row" style="margin-bottom:4px;">
            <div class="check-item">
                <span class="check-box">{{ $checked($la->recommendation === 'for_approval') }}</span> For Approval
            </div>
            <div class="check-item">
                <span class="check-box">{{ $checked($la->recommendation === 'for_disapproval') }}</span> For Disapproval
            </div>
        </div>
        @if($la->recommendation_disapproval_reason)
        <div style="font-size:8px;">(reason) {{ $la->recommendation_disapproval_reason }}</div>
        @endif

        <div class="field-label" style="margin-top:8px;margin-bottom:3px;">7.C/D — Approved / Disapproved</div>
        @foreach(\App\Models\LeaveApplication::APPROVAL_STATUS_SELECT as $key => $label)
        <div class="check-item" style="margin-bottom:2px;">
            <span class="check-box">{{ $checked($la->approval_status === $key) }}</span> {{ $label }}
        </div>
        @endforeach
        @if($la->approval_others_specify)
        <div style="font-size:8px;margin-top:2px;">(specify) {{ $la->approval_others_specify }}</div>
        @endif

        <div class="signature-block" style="margin-top:14px;">
            <div class="sig-line">{{ strtoupper($la->authorized_official_approval ?? '') }}</div>
            <div class="sig-label">Authorized Official</div>
        </div>
    </div>
</div>

</body>
</html>
