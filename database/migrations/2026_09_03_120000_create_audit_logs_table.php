<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            
            $table->string('action_type'); // approval, cancellation, view, export, etc.
            $table->string('action'); // approved, disapproved, returned, viewed, exported, etc.
            $table->text('description')->nullable();
            
            // For approval workflow tracking
            $table->unsignedInteger('step_order')->nullable();
            $table->foreignId('leave_application_id')->nullable()->constrained('leave_applications')->nullOnDelete();
            
            // Timing information for bottleneck analysis
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            // Additional details stored as JSON
            $table->json('details')->nullable();
            
            $table->timestamps();

            // Indexes for performance
            $table->index(['action_type', 'action']);
            $table->index('step_order');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['office_id', 'created_at']);
            $table->index(['division_id', 'created_at']);
            $table->index('leave_application_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('audit_logs');
    }
};