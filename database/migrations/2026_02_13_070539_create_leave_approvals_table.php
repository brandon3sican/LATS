<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained('leave_applications')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->foreignId('approver_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('action'); // approved/returned/disapproved
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at');

            $table->timestamps();

            $table->index(['leave_application_id', 'step_order']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('leave_approvals');
    }
};
