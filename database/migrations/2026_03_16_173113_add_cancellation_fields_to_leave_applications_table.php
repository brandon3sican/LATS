<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->string('cancellation_status')->nullable()->after('status'); // 'pending', 'approved', 'rejected'
            $table->text('cancellation_reason')->nullable()->after('cancellation_status');
        });
    }

    public function down(): void {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn(['cancellation_status', 'cancellation_reason']);
        });
    }
};
