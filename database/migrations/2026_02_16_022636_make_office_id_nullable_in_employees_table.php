<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Allow office_id to be NULL
            $table->unsignedBigInteger('office_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Revert to NOT NULL (Only if you roll back)
            $table->unsignedBigInteger('office_id')->nullable(false)->change();
        });
    }
};
