<?php

namespace Database\Seeders;

use App\Models\NurseAvailability;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NurseAvailability::create([
            'user_id' => '2',
            'day' => 'Tuesday',
            'date' => '2023-05-17',
            'start_shift_time' => '12:00:00',
            'end_shift_time' => '15:00:00',
            'status' => 'available',
        ]);

        NurseAvailability::create([
            'user_id' => '2',
            'day' => 'Tuesday',
            'date' => '2023-05-08',
            'start_shift_time' => '12:00:00',
            'end_shift_time' => '15:00:00',
            'status' => 'unavailable',
        ]);

        NurseAvailability::create([
            'user_id' => '2',
            'day' => 'Wednesday',
            'date' => '2023-05-09',
            'start_shift_time' => '13:00:00',
            'end_shift_time' => '17:00:00',
            'status' => 'assigned',
            'hospice_id' => '1',
        ]);
    }
}
