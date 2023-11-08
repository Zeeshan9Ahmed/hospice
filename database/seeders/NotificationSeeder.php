<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Notification::create([
            'sender_id' => '2',
            'receiver_id' => '1',
            'post_id' => '1',
            'type' => 'Case Accepted',
            'message' => 'Nurse has accepted your case'
        ]);
    }
}
