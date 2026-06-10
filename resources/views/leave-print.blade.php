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

        /* ── Agency header (mirrors pdf/service-record-header) ── */
        .agency-header { width: 100%; margin-bottom: 4px; }
        .agency-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .agency-table td { vertical-align: middle; }
        .agency-logo { height: 64px; width: auto; }
        .agency-name { font-size: 13px; font-weight: bold; letter-spacing: 0.2px; }
        .agency-line { font-size: 8px; color: #111; }
        .sep-blue { height: 2px; background: #003399; margin: 5px 0 2px; }
        .sep-red { height: 3px; background: #cc0000; margin-bottom: 6px; }

        h2 {
            text-align: center;
            font-size: 13px;
            letter-spacing: 1px;
            margin: 2px 0 1px;
        }

        .sub-header {
            text-align: center;
            font-size: 8px;
            margin-bottom: 8px;
            color: #444;
        }

        .field-label { font-size: 7.5px; font-weight: bold; flex-shrink: 0; }

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

        .check-row { display: flex; align-items: center; gap: 16px; margin: 4px 0; font-size: 9px; }
        .check-item { display: flex; align-items: center; gap: 4px; }
        .check-box {
            width: 10px; height: 10px;
            border: 1px solid #000;
            display: inline-block;
            text-align: center;
            line-height: 10px;
            font-size: 8px;
            flex-shrink: 0;
        }
        .legal { font-size: 6.5px; color: #555; font-style: italic; }

        .credits-table { width: 100%; border-collapse: collapse; font-size: 8px; }
        .credits-table th, .credits-table td { border: 1px solid #000; padding: 2px 4px; text-align: center; }
        .credits-table th { background: #f0f0f0; font-size: 7.5px; }

        .signature-block { margin-top: 14px; }
        .sig-line {
            border-top: 1px solid #000;
            padding-top: 2px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            width: 80%;
            margin: 0 auto;
        }
        .sig-label { text-align: center; font-size: 7.5px; color: #555; margin-top: 1px; }

        .medical-notice { font-size: 7.5px; font-style: italic; color: #555; margin-top: 4px; }

        .print-bar {
            background: #1e293b; color: white; padding: 10px 20px;
            display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
        }
        .print-bar button {
            background: #16a34a; color: white; border: none;
            padding: 6px 18px; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer;
        }
        .print-bar a.back {
            background: #af0505; color: white; text-decoration: none;
            padding: 6px 18px; border-radius: 6px; font-size: 12px; font-weight: bold; margin-left: auto;
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
    use App\Models\LeaveApplication;

    $la       = $leaveApplication;
    $profile  = $la->profile;
    $division  = $profile?->division;
    $credits  = $la->certification_leave_credits ?? [];
    $checked  = fn(bool $cond): string => $cond ? '✓' : '';

    $legalBases = [
        'vacation'           => '(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)',
        'mandatory_forced'   => '(Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292)',
        'sick'               => '(Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)',
        'maternity'          => '(R.A. No. 11210 / IRR)',
        'paternity'          => '(R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended)',
        'special_privilege'  => '(Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292)',
        'solo_parent'        => '(R.A. No. 8972 / CSC MC No. 8, s. 2004)',
        'study'              => '(Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)',
        'vawc'               => '(R.A. No. 9262 / CSC MC No. 15, s. 2005)',
        'rehabilitation'     => '(Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292)',
        'special_women'      => '(R.A. No. 9710 / CSC MC No. 25, s. 2010)',
        'emergency_calamity' => '(CSC MC No. 2, s. 2012, as amended)',
        'adoption'           => '(R.A. No. 8552)',
        'wellness'           => '(MTWD Wellness Leave)',
        'others'             => '',
    ];

    $recommenderRole = LeaveApplication::divisionSignatoryRole($division);
    $approverRole    = LeaveApplication::isManagerialPosition($la->position) ? 'General Manager' : 'Designated Signatory';
@endphp

{{-- ── MTWD Header ── --}}
<div class="agency-header">
    <table class="agency-table">
        <tr>
            <td style="width:18%; text-align:right; padding-right:8px;">
                <img class="agency-logo" src="{{ asset('images/MTWD-Logo.png') }}" alt="MTWD Logo">
            </td>
            <td style="width:64%; text-align:center; line-height:1.35;">
                <div class="agency-line">Republic of the Philippines</div>
                <div class="agency-name">METROPOLITAN TUGUEGARAO WATER DISTRICT</div>
                <div class="agency-line">Main Avenue, San Gabriel, Tuguegarao City</div>
                <div class="agency-line">Tel. No. (078) 844-1586; 844-7309; Telefax: (078) 844-9136</div>
                <div class="agency-line">Website: www.mtwd.gov.ph</div>
            </td>
            <td style="width:18%; text-align:left; padding-left:8px;">
                <img class="agency-logo" src="{{ asset('images/Bagong-Pilipinas-Logo.png') }}" alt="Bagong Pilipinas">
            </td>
        </tr>
    </table>
    <div class="sep-blue"></div>
    <div class="sep-red"></div>
</div>

<h2>APPLICATION FOR LEAVE</h2>
<p class="sub-header">CS Form No. 6, Revised 2020</p>

{{-- Header Info --}}
<div class="grid-3" style="gap:12px;margin-bottom:6px;">
    <div>
        <div class="field-label">1. Office/Department</div>
        <div class="field-value">{{ strtoupper($division?->name ?? '') }}</div>
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
        <div class="field-label" style="margin-bottom:3px;">6.A — Type of Leave to be Availed Of</div>
        @foreach(LeaveApplication::LEAVE_TYPE_SELECT as $key => $label)
        <div class="check-item" style="margin-bottom:2px;align-items:flex-start;">
            <span class="check-box">{{ $checked($la->leave_type === $key) }}</span>
            <span>{{ $label }} <span class="legal">{{ $legalBases[$key] ?? '' }}</span></span>
        </div>
        @endforeach
    </div>

    {{-- Right: Details --}}
    <div>
        <div class="field-label" style="margin-bottom:3px;">6.B — Details of Leave</div>
        <div style="font-size:8px;font-weight:bold;">In case of Vacation / Special Privilege Leave:</div>
        <div class="check-row" style="margin:2px 0 4px;">
            <div class="check-item"><span class="check-box">{{ $checked($la->details_location === 'within_philippines') }}</span> Within the Philippines</div>
            <div class="check-item"><span class="check-box">{{ $checked($la->details_location === 'abroad') }}</span> Abroad</div>
        </div>
        <div style="font-size:8px;">(specify) {{ $la->details_location_specific }}</div>

        <div style="font-size:8px;font-weight:bold;margin-top:6px;">In case of Sick Leave:</div>
        <div class="check-row" style="margin:2px 0;">
            <div class="check-item"><span class="check-box">{{ $checked($la->details_sick_leave === 'in_hospital') }}</span> In Hospital</div>
            <div class="check-item"><span class="check-box">{{ $checked($la->details_sick_leave === 'out_patient') }}</span> Out Patient</div>
        </div>
        <div style="font-size:8px;">(specify illness) {{ $la->details_sick_leave_specific }}</div>

        @if($la->details_special_benefits_women)
        <div style="font-size:8px;margin-top:4px;"><strong>Special Leave Benefits for Women:</strong> {{ $la->details_special_benefits_women }}</div>
        @endif

        <div style="font-size:8px;font-weight:bold;margin-top:6px;">Study / Other Purpose:</div>
        @foreach(LeaveApplication::STUDY_LEAVE_SELECT as $key => $label)
        <div class="check-item" style="margin-bottom:1px;">
            <span class="check-box">{{ $checked($la->details_study_leave === $key) }}</span> {{ $label }}
        </div>
        @endforeach
        @foreach(LeaveApplication::OTHER_PURPOSE_SELECT as $key => $label)
        <div class="check-item" style="margin-bottom:1px;">
            <span class="check-box">{{ $checked($la->details_other_purpose === $key) }}</span> {{ $label }}
        </div>
        @endforeach
    </div>
</div>

<div class="grid-3" style="gap:12px;margin-top:6px;">
    <div>
        <div class="field-label">6.C — Number of Working Days</div>
        <div class="field-value" style="font-size:12px;font-weight:bold;text-align:center;">{{ rtrim(rtrim(number_format($la->days_applied_number ?? 0, 1), '0'), '.') }}</div>
    </div>
    <div>
        <div class="field-label">Inclusive Dates</div>
        <div class="field-value" style="text-transform:none;">
            @if($la->inclusiveDates->isNotEmpty())
                {{ $la->inclusiveDates->map(fn($d) => $d->date?->format('M d, Y'))->filter()->implode(', ') }}
            @elseif($la->from && $la->to)
                {{ $la->from->format('F d, Y') }} – {{ $la->to->format('F d, Y') }}
            @endif
        </div>
    </div>
    <div>
        <div class="field-label">6.D — Commutation</div>
        <div class="check-row" style="margin-top:2px;">
            <div class="check-item"><span class="check-box">{{ $checked($la->commutation === 'not_requested') }}</span> Not Requested</div>
            <div class="check-item"><span class="check-box">{{ $checked($la->commutation === 'requested') }}</span> Requested</div>
        </div>
    </div>
</div>

@if($la->requires_medical_certificate)
<p class="medical-notice">* Medical certificate required (sick leave filed in advance or exceeding 5 days).</p>
@endif

@php
    $req = LeaveApplication::requirementsFor($la->leave_type, $la->details_other_purpose);
    $docCount = is_array($la->supporting_documents) ? count($la->supporting_documents) : 0;
@endphp
<div style="font-size:7px;color:#444;margin-top:5px;border:1px solid #ccc;padding:3px 5px;">
    @if($req['filing'] !== '')
        <div><strong>Filing rule:</strong> {{ $req['filing'] }}</div>
    @endif
    @if(!empty($req['documents']))
        <div style="margin-top:1px;"><strong>Documentary requirements:</strong> {{ implode(' ', array_map(fn($d) => '• '.$d, $req['documents'])) }}</div>
    @endif
    @if($la->requires_clearance)
        <div style="margin-top:1px;"><strong>Note:</strong> Requires clearance from money, property, and work-related accountabilities (30+ calendar days or terminal leave).</div>
    @endif
    <div style="margin-top:1px;"><strong>Documents attached:</strong> {{ $docCount }} file(s).</div>
</div>

<div class="signature-block">
    <div class="sig-line">{{ strtoupper($profile?->full_name ?? '') }}</div>
    <div class="sig-label">Signature of Applicant</div>
</div>

{{-- 7. Action on Application --}}
<div class="section-title" style="margin-top:12px;">7. Details of Action on Application</div>

<div class="grid-2" style="gap:16px;">
    {{-- 7.A Leave Credits + two signatories --}}
    <div>
        <div class="field-label" style="margin-bottom:4px;">7.A — Certification of Leave Credits</div>
        <table class="credits-table">
            <thead>
                <tr><th></th><th>Vacation Leave</th><th>Sick Leave</th></tr>
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

        <div class="grid-2" style="gap:10px;margin-top:8px;">
            <div class="signature-block" style="margin-top:6px;">
                <div class="sig-line">{{ strtoupper($la->certificationHrStaff?->full_name ?? '') }}</div>
                <div class="sig-label">Designated HR Employee (Leave)</div>
            </div>
            <div class="signature-block" style="margin-top:6px;">
                <div class="sig-line">{{ strtoupper($la->certificationHrChief?->full_name ?? '') }}</div>
                <div class="sig-label">HR Division Chief</div>
            </div>
        </div>
    </div>

    {{-- 7.B Recommendation + 7.C/D Approval --}}
    <div>
        <div class="field-label" style="margin-bottom:3px;">7.B — Recommendation</div>
        <div class="check-row" style="margin-bottom:4px;">
            <div class="check-item"><span class="check-box">{{ $checked($la->recommendation === 'for_approval') }}</span> For Approval</div>
            <div class="check-item"><span class="check-box">{{ $checked($la->recommendation === 'for_disapproval') }}</span> For Disapproval</div>
        </div>
        @if($la->recommendation_disapproval_reason)
        <div style="font-size:8px;">(reason) {{ $la->recommendation_disapproval_reason }}</div>
        @endif
        <div class="signature-block" style="margin-top:8px;">
            <div class="sig-line">{{ strtoupper($la->recommendationSignatory?->full_name ?? '') }}</div>
            <div class="sig-label">{{ $recommenderRole }}</div>
        </div>

        <div class="field-label" style="margin-top:10px;margin-bottom:3px;">7.C/D — Approved / Disapproved</div>
        @foreach(LeaveApplication::APPROVAL_STATUS_SELECT as $key => $label)
        <div class="check-item" style="margin-bottom:2px;">
            <span class="check-box">{{ $checked($la->approval_status === $key) }}</span> {{ $label }}
        </div>
        @endforeach
        @if($la->approval_others_specify)
        <div style="font-size:8px;margin-top:2px;">(specify) {{ $la->approval_others_specify }}</div>
        @endif

        <div class="signature-block" style="margin-top:10px;">
            <div class="sig-line">{{ strtoupper($la->approvalSignatory?->full_name ?? '') }}</div>
            <div class="sig-label">{{ $approverRole }}</div>
        </div>
    </div>
</div>

</body>
</html>
