<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['key' => 'super_admin', 'name' => 'Super Admin'],
            ['key' => 'office_admin', 'name' => 'Office Admin'],
            ['key' => 'employee', 'name' => 'Employee'],
            ['key' => 'approver_division_chief', 'name' => 'Division Chief (Approver)'],
            ['key' => 'approver_personnel', 'name' => 'Personnel (Approver)'],
            ['key' => 'approver_chief_personnel', 'name' => 'Chief Personnel (Approver)'],
            ['key' => 'approver_ard_ms', 'name' => 'ARD-MS (Approver)'],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(['key' => $r['key']], $r);
        }
    }
}
