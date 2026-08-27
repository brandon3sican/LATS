<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Insert the Wellness Leave Type
        $leaveTypeId = DB::table('leave_types')->insertGetId([
            'code' => 'WL',
            'name' => 'Wellness Leave',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $leaveType = DB::table('leave_types')->where('code', 'WL')->first();

        if ($leaveType) {
            DB::table('leave_required_documents')->where('leave_type_id', $leaveType->id)->delete();
            DB::table('leave_types')->where('id', $leaveType->id)->delete();
        }
    }
};
