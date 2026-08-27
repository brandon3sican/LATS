<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Division;
use App\Models\Role;

class ImportLegacyEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fetch raw data from the imported 'lats_employee_details' table
        $legacyData = DB::table('lats_employee_details')->get();

        $count = 0;
        $defaultPassword = Hash::make('password123');

        foreach ($legacyData as $row) {
            // --- A. PARSE NAME (Format: LAST, FIRST MIDDLE) ---
            $fullName = $row->NAME;
            $parts = explode(',', $fullName);

            $lastName = trim($parts[0] ?? '');
            $rest = trim($parts[1] ?? '');

            // Split First and Middle names
            $nameParts = explode(' ', $rest);
            $middleName = '';
            $firstName = $rest;

            if (count($nameParts) > 1) {
                $middleName = array_pop($nameParts); // Last part is usually middle name
                $firstName = implode(' ', $nameParts);
            }

            $lastName = empty($lastName) ? 'Unknown' : $lastName;
            $firstName = empty($firstName) ? 'Unknown' : $firstName;

            // --- B. FIND OR CREATE OFFICE ---
            $officeName = trim($row->OFFICE);
            $officeId = null;

            if (!empty($officeName)) {
                $code = Str::slug($officeName);
                $office = Office::firstOrCreate(
                    ['name' => $officeName],
                    ['office_code' => strtoupper($code), 'address' => 'Imported Address']
                );
                $officeId = $office->id;
            }

            // --- C. FIND OR CREATE DIVISION ---
            $divisionName = trim($row->DIVISION);
            $divisionId = null;

            if (!empty($divisionName) && $officeId) {
                $division = Division::firstOrCreate(
                    ['name' => $divisionName, 'office_id' => $officeId]
                );
                $divisionId = $division->id;
            }

            // --- D. GENERATE UNIQUE EMAIL ---
            $emailBase = Str::lower(Str::slug($firstName . '.' . $lastName));
            $email = $emailBase . '@denr.temp';

            $counter = 1;
            while (User::where('email', $email)->exists()) {
                $email = $emailBase . $counter . '@denr.temp';
                $counter++;
            }

            // --- E. CREATE USER ACCOUNT ---
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => $middleName,
                'email' => $email,
                'password' => $defaultPassword,
            ]);

            // Assign 'employee' role
            $role = Role::firstOrCreate(['key' => 'employee'], ['name' => 'Employee']);
            $user->roles()->attach($role->id);

            // --- F. CREATE EMPLOYEE DETAILS (With Sex) ---
            Employee::create([
                'user_id' => $user->id,
                'office_id' => $officeId,
                'division_id' => $divisionId,
                'position_title' => $row->{'CURRENT POSITION'} ?? 'Pending',
                'salary_grade' => is_numeric($row->SG) ? $row->SG : null,
                'sex' => $row->SEX,
                'status' => 'active',
            ]);

            $count++;
        }

        $this->command->info("Successfully imported {$count} employees with gender data!");
    }
}
