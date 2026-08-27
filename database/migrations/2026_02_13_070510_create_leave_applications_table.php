<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types');

            $table->date('date_filed');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('working_days_requested', 5, 2)->unsigned()->default(1);

            $table->string('status')->default('pending'); // pending/returned/disapproved/approved
            $table->unsignedInteger('current_step_order')->default(1);

            $table->json('details_json')->nullable();
            $table->string('commutation')->nullable(); // optional

            $table->timestamps();

            $table->index(['office_id', 'status', 'current_step_order'], 'office_status_step_index');
        });
    }

    public function down(): void {
        Schema::dropIfExists('leave_applications');
    }
};
