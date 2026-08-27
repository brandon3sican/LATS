<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\ApprovalStep;
use App\Models\Office;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the new Chief Admin role safely
        Role::firstOrCreate(
            ['key' => 'approver_chief_admin'],
            ['name' => 'Chief Admin (Approver)']
        );

        // 2. Move existing ARD steps from Step 4 down to Step 5
        ApprovalStep::where('role_key', 'approver_ard_ms')->update(['step_order' => 5]);

        // 3. Insert Chief Admin as Step 4 for all existing offices
        $offices = Office::all();
        foreach($offices as $office) {
            ApprovalStep::updateOrCreate(
                ['office_id' => $office->id, 'step_order' => 4],
                ['role_key' => 'approver_chief_admin', 'name' => 'Chief Admin']
            );
        }
    }

    public function down(): void
    {
        // Revert the changes if you ever need to rollback
        ApprovalStep::where('role_key', 'approver_chief_admin')->delete();
        ApprovalStep::where('role_key', 'approver_ard_ms')->update(['step_order' => 4]);
        Role::where('key', 'approver_chief_admin')->delete();
    }
};
