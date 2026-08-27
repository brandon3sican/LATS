<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add the new columns
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->after('id')->nullable();
            $table->string('first_name')->after('last_name')->nullable();
            $table->string('middle_name')->nullable()->after('first_name');
        });

        // 2. Migrate existing data (Split 'name' into First and Last)
        // This is a basic split; manual cleanup might be needed for complex names.
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            if ($user->name) {
                $parts = explode(' ', $user->name, 2);
                $first = $parts[0];
                $last = $parts[1] ?? ''; // If no last name, leave empty

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'first_name' => $first,
                        'last_name' => $last
                    ]);
            }
        }

        // 3. Make columns required and drop the old 'name' column
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id')->nullable();
        });

        // Restore data
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $fullName = trim("{$user->first_name} {$user->last_name}");
            DB::table('users')->where('id', $user->id)->update(['name' => $fullName]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'first_name', 'middle_name']);
        });
    }
};
