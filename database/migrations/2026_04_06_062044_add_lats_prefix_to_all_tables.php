<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Get the exact database name from your configuration (e.g., lais_db)
        $dbName = DB::connection()->getDatabaseName();

        // 2. Query MySQL directly to ONLY get tables belonging to this specific database
        $tables = DB::select("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ?", [$dbName]);

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;

            // 3. Rename the table, strictly enforcing the database name in the SQL command
            if (!str_starts_with($tableName, 'lats_')) {
                DB::statement("RENAME TABLE `{$dbName}`.`{$tableName}` TO `{$dbName}`.`lats_{$tableName}`");
            }
        }
    }

    public function down(): void
    {
        $dbName = DB::connection()->getDatabaseName();
        $tables = DB::select("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ?", [$dbName]);

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;

            if (str_starts_with($tableName, 'lats_')) {
                // Remove the 5-character prefix ('lats_') to revert
                $originalName = substr($tableName, 5);
                DB::statement("RENAME TABLE `{$dbName}`.`{$tableName}` TO `{$dbName}`.`{$originalName}`");
            }
        }
    }
};
