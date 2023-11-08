<?php

namespace Database\Seeders;

use App\Models\HospiceCase;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HospiceCase::create([
            'user_id' => '1',
            'patient_name' => 'Alex',
            'location' => 'D15 street 11',
            'discipline_needed' => 'RN',
            'nurse_id' => '2',
            'dob' => '1997-03-22',
            'phone_number' => '003232484762',
            'gender' => 'Male',
            'status' => 'completed',
            'is_sheet_filled' => 1,
            'start_date' => '2023-05-16',
            'end_date' => '2023-05-17'
        ]);

        HospiceCase::create([
            'user_id' => '1',
            'patient_name' => 'john',
            'location' => 'B0 street 2',
            'discipline_needed' => 'RN, LVN',
            'nurse_id' => '3',
            'status' => 'inprocess',
            'is_sheet_filled' => 0,
            'start_date' => '2023-05-20',
            'end_date' => '2023-05-23'
        ]);

        HospiceCase::create([
            'user_id' => '1',
            'patient_name' => 'alice',
            'location' => 'A5 street 10',
            'discipline_needed' => 'RN, HCC',
            'status' => 'available',
            'is_sheet_filled' => 0,
            'start_date' => '2023-05-24',
            'end_date' => '2023-05-26'
        ]);

        HospiceCase::create([
            'user_id' => '1',
            'patient_name' => 'alice',
            'location' => 'A5 street 10',
            'discipline_needed' => 'RN, HCC',
            'status' => 'pending',
            'is_sheet_filled' => 0,
            'start_date' => '2023-05-24',
            'end_date' => '2023-05-26'
        ]);
    }
}
