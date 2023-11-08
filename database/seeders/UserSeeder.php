<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'email' => 'hospice@getnada.com',
            'password' => Hash::make('Abcd@1234'),
            'phone_number' => '02332313132',
            'business_name' => 'Hospice ABC',
            'role' => 'hospice',
            'otp' => '1234',
            'is_approved' => 1
        ]);

        User::create([
            'first_name' => 'Nurse',
            'last_name' => '1',
            'email' => 'nurse1@getnada.com',
            'password' => Hash::make('Abcd@1234'),
            'phone_number' => '02332329384',
            'discipline' => 'RN',
            'role' => 'nurse',
            'otp' => '1234',
            'is_approved' => 1
        ]);

        User::create([
            'first_name' => 'Nurse',
            'last_name' => '2',
            'email' => 'nurse2@getnada.com',
            'password' => Hash::make('Abcd@1234'),
            'phone_number' => '02332320000',
            'discipline' => 'LVN',
            'otp' => '1234',
            'role' => 'nurse'
        ]);

        User::create([
            'first_name' => 'Nurse',
            'last_name' => '3',
            'email' => 'nurse3@getnada.com',
            'password' => Hash::make('Abcd@1234'),
            'phone_number' => '02344520000',
            'discipline' => 'LVN',
            'otp' => '1234',
            'role' => 'nurse'
        ]);

    }
}
