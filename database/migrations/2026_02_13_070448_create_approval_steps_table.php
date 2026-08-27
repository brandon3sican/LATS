<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->unsignedInteger('step_order'); // 1..n
            $table->string('role_key');            // approver_personnel, etc.
            $table->string('name');                // "Personnel"
            $table->timestamps();

            $table->unique(['office_id', 'step_order']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('approval_steps');
    }
};
