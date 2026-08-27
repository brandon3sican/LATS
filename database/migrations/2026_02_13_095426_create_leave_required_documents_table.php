<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_required_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();

            $table->string('name');              // e.g. "Medical Certificate"
            $table->string('key')->nullable();   // optional stable key like "medical_cert"
            $table->boolean('is_required')->default(true);

            // JSON rule: when this document is required
            // Example: {"days_gt":5}
            // Example: {"field":"details.abroad","equals":true}
            // Example: {"any":[{"days_gt":5},{"field":"details.hospitalized","equals":true}]}
            $table->json('rule_json')->nullable();

            $table->timestamps();
            $table->index(['leave_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_required_documents');
    }
};
