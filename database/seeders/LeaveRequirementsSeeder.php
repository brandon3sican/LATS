<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{LeaveType, LeaveRequiredDocument};

class LeaveRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        $type = fn(string $code) => LeaveType::where('code', $code)->first();

        // VL
        if ($vl = $type('VL')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $vl->id, 'key' => 'travel_clearance'],
                [
                    'name' => 'Travel Authority/Clearance (if abroad)',
                    'is_required' => true,
                    'rule_json' => ['field' => 'details.abroad', 'equals' => true],
                ]
            );

            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $vl->id, 'key' => 'clearance_30_days'],
                [
                    'name' => 'Clearance from money/property/work accountabilities (if 30+ days)',
                    'is_required' => true,
                    'rule_json' => ['days_gte' => 30],
                ]
            );
        }

        // SL
        if ($sl = $type('SL')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $sl->id, 'key' => 'medical_cert'],
                [
                    'name' => 'Medical Certificate (if sick leave > 5 days)',
                    'is_required' => true,
                    'rule_json' => ['days_gt' => 5],
                ]
            );

            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $sl->id, 'key' => 'affidavit_no_consult'],
                [
                    'name' => 'Affidavit (if no medical consultation)',
                    'is_required' => false,
                    'rule_json' => ['field' => 'details.no_consultation', 'equals' => true],
                ]
            );
        }

        // ML
        if ($ml = $type('ML')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $ml->id, 'key' => 'proof_pregnancy'],
                [
                    'name' => 'Proof of pregnancy (ultrasound / doctor’s certificate)',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );

            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $ml->id, 'key' => 'cs_form_6a'],
                [
                    'name' => 'CS Form No. 6a (Notice of Allocation), if needed',
                    'is_required' => true,
                    'rule_json' => ['field' => 'details.ml_need_cs6a', 'equals' => 'yes'],
                ]
            );
        }

        // PL
        if ($pl = $type('PL')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $pl->id, 'key' => 'proof_delivery'],
                [
                    'name' => 'Proof of child’s delivery (birth cert / medical cert) + marriage contract',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );
        }

        // SOLO
        if ($solo = $type('SOLO')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $solo->id, 'key' => 'solo_parent_id'],
                [
                    'name' => 'Updated Solo Parent Identification Card',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );
        }

        // STUDY
        if ($study = $type('STUDY')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $study->id, 'key' => 'study_contract'],
                [
                    'name' => 'Study Leave contract (agency head/authorized rep + employee)',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );
        }

        // VAWC
        if ($vawc = $type('VAWC')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $vawc->id, 'key' => 'vawc_support'],
                [
                    'name' => 'VAWC supporting document (BPO/TPO/PPO/certification OR police report + medical cert)',
                    'is_required' => true,
                    'rule_json' => ['field' => 'details.vawc_support', 'in' => ['bpo','tpo_ppo','cert_filed','police_med']],
                ]
            );
        }

        // REHAB
        if ($rehab = $type('REHAB')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $rehab->id, 'key' => 'rehab_letter'],
                [
                    'name' => 'Letter request + medical certificate (rehabilitation)',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );

            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $rehab->id, 'key' => 'govt_concurrence_if_private'],
                [
                    'name' => 'Written concurrence of government physician (if private practitioner)',
                    'is_required' => true,
                    'rule_json' => ['field' => 'details.rehab_physician', 'equals' => 'private'],
                ]
            );
        }

        // WOMEN
        if ($women = $type('WOMEN')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $women->id, 'key' => 'women_medical_docs'],
                [
                    'name' => 'Medical certificate + clinical summary + histopath report/operative details',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );
        }

        // CALAMITY
        if ($cal = $type('CALAMITY')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $cal->id, 'key' => 'calamity_proof'],
                [
                    'name' => 'Proof of eligibility (residence/affected area verification as required)',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );
        }

        // MON
        if ($mon = $type('MON')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $mon->id, 'key' => 'mon_letter'],
                [
                    'name' => 'Letter request to head of agency stating valid reasons',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );
        }

        // TL
        if ($tl = $type('TL')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $tl->id, 'key' => 'proof_separation'],
                [
                    'name' => 'Proof of resignation/retirement/separation',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );

            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $tl->id, 'key' => 'clearance_terminal'],
                [
                    'name' => 'Clearance from money/property/work accountabilities',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );
        }

        // ADOPT
        if ($adopt = $type('ADOPT')) {
            LeaveRequiredDocument::updateOrCreate(
                ['leave_type_id' => $adopt->id, 'key' => 'papa'],
                [
                    'name' => 'Authenticated Pre-Adoptive Placement Authority (DSWD)',
                    'is_required' => true,
                    'rule_json' => null,
                ]
            );
        }
    }
}
