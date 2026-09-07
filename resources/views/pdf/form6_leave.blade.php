<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CS Form No. 6</title>
    <style>
        @page {
            margin: 10mm 10mm 10mm 10mm;
            size: A4 portrait;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9px;
            line-height: 1.2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        td, th {
            border: 1px solid #000;
            padding: 3px;
            vertical-align: top;
        }
        .no-border { border: none !important; }
        .border-bottom { border-bottom: 1px solid #000 !important; }
        .border-top { border-top: 1px solid #000 !important; }
        .border-left { border-left: 1px solid #000 !important; }
        .border-right { border-right: 1px solid #000 !important; }

        .header {
            text-align: left;
            margin-bottom: 5px;
        }
        .header-title {
            font-weight: bold;
            font-style: italic;
            font-size: 8px;
        }
        .form-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
        }
        .section-header {
            background-color: #ddd;
            font-weight: bold;
            text-align: center;
            padding: 2px;
            font-style: italic;
        }
        .label {
            font-size: 9px;
            margin-bottom: 2px;
            display: block;
        }
        .input-val {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .checkbox-group {
            margin-bottom: 4px;
        }
        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            font-size: 15px;
            line-height: 11px;
            font-weight: bold;
            margin-right: 4px;
            overflow: hidden;
        }
        .underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 20px;
        }
        /* PAGE 2 SPECIFIC STYLES */
        .page-break {
            page-break-before: always;
        }
        .instructions-box {
            border: 1px solid #000;
        }
        .instructions-title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .instructions-list {
            font-size: 9px;
            text-align: justify;
        }
        .instructions-list dt {
            font-weight: bold;
            margin-top: 5px;
        }
        .instructions-list dd {
            margin-left: 15px;
            margin-bottom: 5px;
            margin-top: 2px;
        }
        .sub-list {
            list-style-type: none;
            padding-left: 5px;
            margin-top: 2px;
        }
        .sub-list li {
            margin-bottom: 2px;
            padding-left: 15px;
            text-indent: -15px;
        }
        .footer-note {
            margin-top: 105px;
            font-style: italic;
            font-size: 9px;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    @php
        $officeId = $leave->employee->office_id ?? null;
        $divisionId = $leave->employee->division_id ?? null;

        // Query for Chief Personnel
        $chiefPersonnelUser = \App\Models\User::whereHas('employee', fn($q) => $q->where('office_id', $officeId))
            ->whereHas('roles', fn($q) => $q->whereIn('key', ['approver_personnel', 'approver_chief_personnel']))
            ->first();

        // Query for Division Chief
        $chiefUser = \App\Models\User::whereHas('employee', fn($q) => $q->where('office_id', $officeId)->where('division_id', $divisionId))
            ->whereHas('roles', fn($q) => $q->where('key', 'approver_division_chief'))
            ->first();

        // Query for ARD/MS
        $ardUser = \App\Models\User::whereHas('employee', fn($q) => $q->where('office_id', $officeId))
            ->whereHas('roles', fn($q) => $q->where('key', 'approver_ard_ms'))
            ->first();

        $getBase64Signature = function($path) {
            if (empty($path)) return null;

            // Try the public path first (since you ran storage:link)
            $fullPath = public_path('storage/' . $path);

            if (!file_exists($fullPath)) {
                // Fallback to the strict storage path
                $fullPath = storage_path('app/public/' . $path);
            }

            if (!file_exists($fullPath)) return null;

            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mime = ($extension == 'jpg') ? 'jpeg' : $extension; // Base64 requires 'jpeg' not 'jpg'
            $data = file_get_contents($fullPath);

            return 'data:image/' . $mime . ';base64,' . base64_encode($data);
        };

        $formatName = function($user) {
            if (!$user) return null;
            $mi = $user->middle_name ? strtoupper(substr($user->middle_name, 0, 1)) . '. ' : '';
            return strtoupper($user->first_name . ' ' . $mi . $user->last_name);
        };

        $chiefPersonnelName = $formatName($chiefPersonnelUser) ?? 'CHIEF PERSONNEL';
        $chiefName = $formatName($chiefUser) ?? 'DIVISION CHIEF';
        $ardName = $formatName($ardUser) ?? 'REGIONAL DIRECTOR';

        // Variables for actions and dates
        $personnelAction = null;
        $personnelDate = null;

        $chiefAction = null;
        $chiefDate = null;
        $chiefRemarksArr = [];

        $ardAction = null;
        $ardDate = null;
        $ardRemarksArr = [];

        if ($leave->approvals) {
            // Sort by created_at so we process the actions in timeline order
            $sortedApprovals = $leave->approvals->sortBy('created_at');

            foreach ($sortedApprovals as $approval) {
                // Identify the user who made the approval
                $approverId = $approval->approver_user_id ?? $approval->user_id ?? $approval->approver_id;

                // Match based on User ID or Step Order fallback
                $isChief = ($chiefUser && $approverId == $chiefUser->id) || $approval->step_order == 1;
                $isPersonnel = ($chiefPersonnelUser && $approverId == $chiefPersonnelUser->id) || in_array($approval->step_order, [2, 3]);
                $isArd = ($ardUser && $approverId == $ardUser->id) || $approval->step_order >= 5;

                if ($isPersonnel) {
                    $personnelAction = strtolower($approval->action);
                    $personnelDate = $approval->acted_at ?? $approval->created_at;
                } elseif ($isChief) {
                    $chiefAction = strtolower($approval->action);
                    $chiefDate = $approval->acted_at ?? $approval->created_at;
                    if (!empty(trim($approval->remarks))) {
                        $chiefRemarksArr[] = $approval->remarks;
                    }
                } elseif ($isArd) {
                    $ardAction = strtolower($approval->action);
                    $ardDate = $approval->acted_at ?? $approval->created_at;
                    if (!empty(trim($approval->remarks))) {
                        $ardRemarksArr[] = $approval->remarks;
                    }
                }
            }
        }

        $chiefRemarks = implode(' | ', array_unique($chiefRemarksArr));
        $ardRemarks = implode(' | ', array_unique($ardRemarksArr));
    @endphp

    {{-- PAGE 1: THE FORM --}}
    <div class="header">
        <span class="header-title">Civil Service Form No. 6<br>Revised 2020</span>
    </div>

    <div style="text-align: center; margin-bottom: -25px; position: relative; min-height: 90px;">
        <img src="{{ public_path('images/denr_logo.png') }}" alt="DENR Logo" style="width: 90px; height: auto; position: absolute; left: 100px; top: 0;">
        <strong>Republic of the Philippines</strong><br>
        <span style="font-weight: bold; color: green; font-size: 10px; font-style: italic;">Department of Environment and Natural Resources</span><br>
        <strong>CORDILLERA ADMINISTRATIVE REGION</strong><br>
        <span style="font-weight: bold; font-style: italic;">DENR Compound, Gibraltar Road, Baguio City</span>
    </div>

    <div class="form-title">APPLICATION FOR LEAVE</div>

    <table>
        <tr>
            <td style="width: 35%;">
                <span class="label">1. OFFICE/DEPARTMENT</span>
                <div class="input-val">{{ $leave->office->name ?? 'DENR-CAR' }}</div>
            </td>
            <td style="width: 20%;">
                <div style="font-size: 8px;">2. NAME (Last)</div>
                <div class="input-val">{{ $leave->employee->user->last_name ?? '' }}</div>
            </td>
            <td style="width: 25%;">
                <div style="font-size: 8px;">(First)</div>
                <div class="input-val">{{ $leave->employee->user->first_name ?? '' }}</div>
            </td>
            <td style="width: 20%;">
                <div style="font-size: 8px;">(Middle)</div>
                <div class="input-val">{{ $leave->employee->user->middle_name ?? '' }}</div>
            </td>
        </tr>
    </table>

    <table style="margin-top: -1px;">
        <tr>
            <td style="width: 35%;">
                <span class="label">3. DATE OF FILING</span>
                <div class="input-val">{{ \Carbon\Carbon::parse($leave->date_filed)->format('F d, Y') }}</div>
            </td>
            <td style="width: 45%;">
                <span class="label">4. POSITION</span>
                <div class="input-val">{{ $leave->employee->position_title ?? 'N/A' }}</div>
            </td>
            <td style="width: 20%;">
                <span class="label">5. SALARY</span>
                <div class="input-val">
                    {{ $leave->getDetail('salary') ? '₱ ' . number_format((float)$leave->getDetail('salary'), 2) : '' }}
                </div>
            </td>
        </tr>
    </table>

    <table><tr><td class="section-header">6. DETAILS OF APPLICATION</td></tr></table>

    <table>
        <tr>
            <td style="width: 60%;">
                <span class="label">6.A TYPE OF LEAVE TO BE AVAILED OF</span>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('VL') ? '✓' : '' }}</span> <b>Vacation Leave</b> <small>(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('FL') ? '✓' : '' }}</span> <b>Mandatory/Forced Leave</b> <small>(Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('SL') ? '✓' : '' }}</span> <b>Sick Leave</b> <small>(Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('ML') ? '✓' : '' }}</span> <b>Maternity Leave</b> <small>(R.A. No. 11210 / IRR issued by CSC, DOLE and SSS)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('PL') ? '✓' : '' }}</span> <b>Paternity Leave</b> <small>(R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('SPL') ? '✓' : '' }}</span> <b>Special Privilege Leave</b> <small>(Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('SOLO') ? '✓' : '' }}</span> <b>Solo Parent Leave</b> <small>(R.A. No. 8972 / CSC MC No. 8, s. 2004)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('STUDY') ? '✓' : '' }}</span> <b>Study Leave</b> <small>(Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('VAWC') ? '✓' : '' }}</span> <b>10-Day VAWC Leave</b> <small>(R.A. No. 9262 / CSC MC No. 15, s. 2005)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('REHAB') ? '✓' : '' }}</span> <b>Rehabilitation Leave</b> <small>(Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('WOMEN') ? '✓' : '' }}</span> <b>Special Leave Benefits for Women</b> <small>(R.A. No. 9710 / CSC MC No. 25, s. 2010)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('CALAMITY') ? '✓' : '' }}</span> <b>Special Emergency (Calamity) Leave</b> <small>(CSC MC No. 2, s. 2012, as amended)</small></div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->isType('ADOPT') ? '✓' : '' }}</span> <b>Adoption Leave</b> <small>(R.A. No. 8552)</small></div>
                <div class="checkbox-group">
                    <span class="label">Others:</span>
                    <div style="margin-left: 20px;">
                        <span class="checkbox">{{ $leave->isType('MON') ? '✓' : '' }}</span> <b>Monetization of Leave Credits</b> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span class="checkbox">{{ $leave->isType('WL') ? '✓' : '' }}</span> <b>Wellness Leave</b><br>
                        <span class="checkbox">{{ $leave->isType('TL') ? '✓' : '' }}</span> <b>Terminal Leave</b>
                    </div>
                </div>
            </td>
            <td style="width: 40%;">
                <span class="label">6.B DETAILS OF LEAVE</span>
                <div class="label" style="margin-top: 5px;"><i>In case of Vacation/Special Privilege Leave:</i></div>
                <div class="checkbox-group"><span class="checkbox">{{ ($leave->isType('VL') && !$leave->getDetail('abroad')) ? '✓' : '' }}</span> Within the Philippines</div>
                <div class="checkbox-group"><span class="checkbox">{{ ($leave->isType('VL') && $leave->getDetail('abroad')) ? '✓' : '' }}</span> Abroad (Specify) <span class="underline" style="width: 80px;">{{ $leave->getDetail('abroad') ? $leave->getDetail('location') : '' }}</span></div>

                <div class="label" style="margin-top: 5px;"><i>In case of Sick Leave:</i></div>
                <div class="checkbox-group"><span class="checkbox">{{ ($leave->isType('SL') && !$leave->getDetail('sl_patient_type')) ? '✓' : '' }}</span> In Hospital (Specify Illness) <span class="underline" style="width: 100px;">{{ ($leave->isType('SL') && !$leave->getDetail('sl_patient_type')) ? $leave->getDetail('illness') : '' }}</span></div>
                <div class="checkbox-group"><span class="checkbox">{{ ($leave->isType('SL') && $leave->getDetail('sl_patient_type')) ? '✓' : '' }}</span> Out Patient (Specify Illness) <span class="underline" style="width: 100px;">{{ ($leave->isType('SL') && $leave->getDetail('sl_patient_type')) ? $leave->getDetail('illness') : '' }}</span></div>

                <div class="label" style="margin-top: 5px;"><i>In case of Special Leave Benefits for Women:</i></div>
                <div class="checkbox-group">-<span class="underline" style="width: 200px;">{{ $leave->isType('WOMEN') ? $leave->getDetail('reason') : '' }}</span></div>

                <div class="label" style="margin-top: 5px;"><i>In case of Study Leave:</i></div>
                <div class="checkbox-group"><span class="checkbox"></span> Completion of Master's Degree<br><span class="checkbox"></span> BAR/Board Examination Review</div>

                <div class="label" style="margin-top: 5px;"><i>Other purpose:</i><span class="underline" style="width: 100px;"></span></div>
                <div class="checkbox-group">
                    <span class="checkbox">{{ $leave->isType('MON') ? '✓' : '' }}</span> Monetization of Leave Credits<br>
                    <span class="checkbox">{{ $leave->isType('TL') ? '✓' : '' }}</span> Terminal Leave<br>
                    <span class="checkbox">{{ $leave->isType('WL') ? '✓' : '' }}</span> Wellness Leave
                </div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width: 60%;">
                <span class="label">6.C NUMBER OF WORKING DAYS APPLIED FOR</span>
                <div class="input-val" style="margin-left: 20px;">{{ $leave->working_days_requested }} day(s)</div>
                <div class="label" style="margin-top: 10px;">INCLUSIVE DATES</div>
                <div class="input-val" style="margin-left: 20px;">{{ \Carbon\Carbon::parse($leave->start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('M d, Y') }}</div>
            </td>
            <td style="width: 40%;">
                <span class="label">6.D COMMUTATION</span>
                <div class="checkbox-group" style="margin-top: 10px;"><span class="checkbox">{{ $leave->commutation === 'not_requested' || $leave->commutation == null ? '✓' : '' }}</span> Not Requested</div>
                <div class="checkbox-group"><span class="checkbox">{{ $leave->commutation === 'requested' ? '✓' : '' }}</span> Requested</div>
                <div style="margin-top: 5px; text-align: center;">
                    <div style="height: 60px;">
                        @if(!empty($leave->employee->user->signature_path))
                            <img src="{{ $getBase64Signature($leave->employee->user->signature_path) }}" style="display: block; margin: 0 auto; height: 60px; margin-bottom: -35px; vertical-align: bottom;">
                        @endif
                    </div>
                    <div style="border-bottom: 1px solid black; width: 80%; margin: 0 auto;"></div>
                    <span class="label">(Signature of Applicant)</span>
                </div>
            </td>
        </tr>
    </table>

    <table><tr><td class="section-header">7. DETAILS OF ACTION ON APPLICATION</td></tr></table>

    <table>
        <tr>
            <td style="width: 60%;">
                <span class="label">7.A CERTIFICATION OF LEAVE CREDITS</span>
                <div class="label" style="margin-bottom: 5px;">As of <span class="underline" style="width: 80px;">{{ $leave->getDetail('credits_as_of') ?? now()->format('F Y') }}</span></div>

                <table style="width: 100%; border: none;">
                    <tr style="text-align: center;">
                        <th style="border: 1px solid #000;"></th>
                        <th style="border: 1px solid #000;">Vacation Leave</th>
                        <th style="border: 1px solid #000;">Sick Leave</th>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;">Total Earned</td>
                        <td style="border: 1px solid #000; text-align: center;">{!! $leave->getDetail('vl_earned') !== null ? $leave->getDetail('vl_earned') : '&nbsp;' !!}</td>
                        <td style="border: 1px solid #000; text-align: center;">{!! $leave->getDetail('sl_earned') !== null ? $leave->getDetail('sl_earned') : '&nbsp;' !!}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;">Less this application</td>
                        <td style="border: 1px solid #000; text-align: center;">{!! $leave->getDetail('vl_less') !== null ? $leave->getDetail('vl_less') : ($leave->isType('VL') ? $leave->working_days_requested : '&nbsp;') !!}</td>
                        <td style="border: 1px solid #000; text-align: center;">{!! $leave->getDetail('sl_less') !== null ? $leave->getDetail('sl_less') : ($leave->isType('SL') ? $leave->working_days_requested : '&nbsp;') !!}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #000;">Balance</td>
                        <td style="border: 1px solid #000; text-align: center;">{!! $leave->getDetail('vl_balance') !== null ? $leave->getDetail('vl_balance') : '&nbsp;' !!}</td>
                        <td style="border: 1px solid #000; text-align: center;">{!! $leave->getDetail('sl_balance') !== null ? $leave->getDetail('sl_balance') : '&nbsp;' !!}</td>
                    </tr>
                </table>

                <div style="margin-top: 5px; text-align: center;">
                    <div style="height: 30px;">
                        @if(in_array($personnelAction, ['approved', 'certified']) && !empty($chiefPersonnelUser->signature_path))
                            <img src="{{ $getBase64Signature($chiefPersonnelUser->signature_path) }}" style="display: block; margin: 0 auto; height: 60px; margin-bottom: -35px; vertical-align: bottom;">
                            <span style="font-size: 8px; font-weight: bold; margin-left: 5px;">{{ \Carbon\Carbon::parse($personnelDate)->format('m/d/y') }}</span>
                        @endif
                    </div>
                    <div style="border-bottom: 1px solid black; width: 60%; margin: 0 auto;">
                        <strong>{{ $chiefPersonnelName }}</strong>
                    </div>
                    <span class="label">(Chief, Personnel Section)</span>
                </div>
            </td>

            <td style="width: 40%;">
                <span class="label">7.B RECOMMENDATION</span>

                {{-- DYNAMIC CHECKBOX LOGIC FOR DIVISION CHIEF ONLY --}}
                <div class="checkbox-group" style="margin-top: 10px;">
                    <span class="checkbox">{{ $chiefAction === 'approved' ? '✓' : '' }}</span> For approval
                </div>
                <div class="checkbox-group">
                    <span class="checkbox">{{ in_array($chiefAction, ['disapproved', 'returned']) ? '✓' : '' }}</span> For disapproval due to / Remarks: <br>

                    {{-- Dynamically inserted Remarks Area ONLY FOR DIVISION CHIEF --}}
                    <div style="border-bottom: 1px solid #000; width: 100%; min-height: 15px; padding-top: 4px; font-weight: bold; font-size: 10px;">
                        {{ $chiefRemarks }}
                    </div>
                </div>

                <div style="margin-top: 5px; text-align: center;">
                    <div style="height: 30px;">
                        @if($chiefAction === 'approved' && !empty($chiefUser->signature_path))
                            <img src="{{ $getBase64Signature($chiefUser->signature_path) }}" style="display: block; margin: 0 auto; height: 60px; margin-bottom: -35px; vertical-align: bottom;"><br>
                            <span style="font-size: 8px; font-weight: bold; margin-left: 200px; margin-bottom: -50px;">{{ \Carbon\Carbon::parse($chiefDate)->format('m/d/y') }}</span>
                        @endif
                    </div>
                    <div style="border-bottom: 1px solid black; width: 80%; margin: 0 auto;">
                        <strong>{{ $chiefName }}</strong>
                    </div>
                    <span class="label">(Chief, Planning Division)</span>
                </div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width: 50%;">
                <span class="label">7.C APPROVED FOR:</span>

                {{-- DYNAMICALLY FILL IF ARD MS APPROVED --}}
                <div style="margin-bottom: 5px;">
                    <span class="underline" style="width: 30px; text-align: center; font-weight: bold;">
                        {{ $ardAction === 'approved' ? $leave->working_days_requested : '' }}
                    </span> days with pay
                </div>
                <div style="margin-bottom: 5px;"><span class="underline" style="width: 30px; text-align: center; font-weight: bold;"></span> days without pay</div>
                <div style="margin-bottom: 5px;"><span class="underline" style="width: 30px; text-align: center; font-weight: bold;"></span> others (Specify)</div>
            </td>

            <td style="width: 50%;">
                <span class="label">7.D DISAPPROVED DUE TO:</span>
                <div style="margin-top: 20px;">

                    {{-- Dynamically inserted Remarks Area ONLY FOR ARD MS --}}
                    <div style="border-bottom: 1px solid #000; width: 100%; min-height: 15px; font-weight: bold; font-size: 10px;">
                        {{ $ardRemarks }}
                    </div>

                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 0;">
                <table style="width: 100%; border: none; margin: 0;">
                    <tr>
                        <td style="width: 30%; border: none;"></td>

                        <td style="width: 40%; text-align: center; border: none; padding-top: 5px;">
                            <div style="height: 30px;">
                                @if($ardAction === 'approved' && !empty($ardUser->signature_path))
                                    <img src="{{ $getBase64Signature($ardUser->signature_path) }}" style="display: block; margin: 0 auto; height: 75px; margin-bottom: -80px; vertical-align: bottom;"><br>
                                    <span style="font-size: 8px; font-weight: bold; margin-left: 200px; margin-bottom: -50px;">{{ \Carbon\Carbon::parse($ardDate)->format('m/d/y') }}</span>
                                @endif
                            </div>
                            <div style="border-bottom: 1px solid black; width: 100%; margin: 0 auto; font-weight: bold; text-transform: uppercase;">
                                {{ $ardName }}
                            </div>
                            <span class="label">(Assistant Regional Director for Management Services)</span>
                        </td>

                        <td style="width: 30%; text-align: center; border: none; vertical-align: bottom; padding-bottom: 10px; padding-right: 10px; padding-left: 10px;">
                            <div style="border: 2px dashed red; background-color: #fff0f0; color: #cc0000; padding: 6px; font-weight: bold; font-size: 9px;">
                                IMPORTANT: PLEASE SUBMIT THIS IMMEDIATELY TO PERSONNEL
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- PAGE 2: INSTRUCTIONS --}}
    <div class="page-break"></div>

    <div class="instructions-box"><div class="instructions-title">INSTRUCTIONS AND REQUIREMENTS</div></div>

    <table class="no-border" style="width: 100%;">
        <tr>
            <td class="no-border" style="width: 50%; padding-right: 10px;">
                <dl class="instructions-list">
                    <div style="margin-bottom: 10px; font-size: 9px;">Application for any type of leave shall be made on this Form and to be accomplished at least in duplicate with documentary requirements, as follows:</div>
                    <dt>1. Vacation leave</dt>
                    <dd>It shall be filed five (5) days in advance, whenever possible, of the effective date of such leave. Vacation leave within in the Philippines or abroad shall be indicated in the form for purposes of securing travel authority and completing clearance from money and work accountabilities.</dd>

                    <dt>2. Mandatory/Forced leave</dt>
                    <dd>Annual five-day vacation leave shall be forfeited if not taken during the year. In case the scheduled leave has been cancelled in the exigency of the service by the head of agency, it shall no longer be deducted from the accumulated vacation leave. Availment of one (1) day or more Vacation Leave (VL) shall be considered for complying the mandatory/forced leave subject to the conditions under Section 25, Rule XVI of the Omnibus Rules Implementing E.O. No. 292.</dd>

                    <dt>3. Sick leave*</dt>
                    <dd>It shall be filed immediately upon employee's return from such leave. If filed in advance or exceeding five (5) days, application shall be accompanied by a medical certificate. In case medical consultation was not availed of, an affidavit should be executed by an applicant.</dd>

                    <dt>4. Maternity leave* – 105 days</dt>
                    <dd>Proof of pregnancy e.g. ultrasound, doctor's certificate on the expected date of delivery. Accomplished Notice of Allocation of Maternity Leave Credits (CS Form No. 6a), if needed. Seconded female employees shall enjoy maternity leave with full pay in the recipient agency.</dd>

                    <dt>5. Paternity leave – 7 days</dt>
                    <dd>Proof of child's delivery e.g. birth certificate, medical certificate and marriage contract.</dd>

                    <dt>6. Special Privilege leave – 3 days</dt>
                    <dd>It shall be filed/approved for at least one (1) week prior to availment, except on emergency cases. Special privilege leave within the Philippines or abroad shall be indicated in the form for purposes of securing travel authority and completing clearance from money and work accountabilities.</dd>

                    <dt>7. Solo Parent leave – 7 days</dt>
                    <dd>It shall be filed in advance or whenever possible five (5) days before going on such leave with updated Solo Parent Identification Card.</dd>

                    <dt>8. Study leave* – up to 6 months</dt>
                    <dd>Shall meet the agency's internal requirements, if any; Contract between the agency head or authorized representative and the employee concerned.</dd>

                    <dt>9. VAWC leave – 10 days</dt>
                    <dd>It shall be filed in advance or immediately upon the woman employee's return from such leave. It shall be accompanied by any of the following supporting documents:
                        <ul class="sub-list">
                            <li>a. Barangay Protection Order (BPO) obtained from the barangay;</li>
                            <li>b. Temporary/Permanent Protection Order (TPO/PPO) obtained from the court;</li>
                            <li>c. If the protection order is not yet issued by the barangay or the court, a certification issued by the Punong Barangay/Kagawad or Prosecutor or the Clerk of Court that the application for the BPO, TPO or PPO has been filed with the said office shall be sufficient to support the application for the ten-day leave; or</li>
                        </ul>
                    </dd>
                </dl>
            </td>

            <td class="no-border" style="width: 50%; padding-left: 10px;">
                <dl class="instructions-list">
                    <ul>
                        <li>d. In the absence of the BPO/TPO/PPO or the certification, a police report specifying the details of the occurrence of violence on the victim and a medical certificate may be considered, at the discretion of the immediate supervisor of the woman employee concerned.</li>
                    </ul>
                    <dt>10. Rehabilitation leave up to 6 months</dt>
                    <dd>Application shall be made within one (1) week from the time of the accident except when a longer period is warranted. Letter request supported by relevant reports such as the police report, if any, Medical certificate on the nature of the injuries, the course of treatment involved, and the need to undergo rest, recuperation, and rehabilitation, as the case may be. Written concurrence of a government physician should be obtained relative to the recommendation for rehabilitation if the attending physician is a private practitioner, particularly on the duration of the period of rehabilitation.</dd>

                    <dt>11. Special leave benefits for women – up to 2 months</dt>
                    <dd>The application may be filed in advance, that is, at least five (5) days prior to the scheduled date of the gynecological surgery that will be undergone by the employee. In case of emergency, the application for special leave shall be filed immediately upon employee's return but during confinement the agency shall be notified of said surgery. The application shall be accompanied by a medical certificate filled out by the proper medical authorities, e.g. the attending surgeon accompanied by a clinical summary reflecting the gynecological disorder which shall be addressed or was addressed by the said surgery; the histopathological report; the operative technique used for the surgery; the duration of the surgery including the peri-operative period (period of confinement around surgery); as well as the employees estimated period of recuperation for the same.</dd>

                    <dt>12. Special Emergency (Calamity) leave up to 5 days</dt>
                    <dd>The special emergency leave can be applied for a maximum of five (5) straight working days or staggered basis within thirty (30) days from the actual occurrence of the natural calamity/disaster. Said privilege shall be enjoyed once a year, not in every instance of calamity or disaster. The head of office shall take full responsibility for the grant of special emergency leave and verification of the employee's eligibility to be granted thereof. Said verification shall include: validation of place of residence based on latest available records of the affected employee; verification that the place of residence is covered in the declaration of calamity area by the proper government agency; and such other proofs as may be necessary.</dd>

                    <dt>13. Monetization of leave credits</dt>
                    <dd>Application for monetization of fifty percent (50%) or more of the accumulated leave credits shall be accompanied by letter request to the head of agency stating the valid and justifiable reasons.</dd>

                    <dt>14. Terminal leave*</dt>
                    <dd>Proof of employee's resignation or retirement or separation from the service.</dd>

                    <dt>15. Adoption Leave</dt>
                    <dd>Application for adoption leave shall be filed with an authenticated copy of the Pre-Adoptive Placement Authority issued by the Department of Social Welfare and Development (DSWD).</dd>
                </dl>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        * For leave of absence for thirty (30) calendar days or more and terminal leave, application shall be accompanied by a clearance from money, property and work-related accountabilities (pursuant to CSC Memorandum Circular No. 2, s. 1985).
    </div>

</body>
</html>
