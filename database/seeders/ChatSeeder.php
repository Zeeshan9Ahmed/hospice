<?php

namespace Database\Seeders;

use App\Models\Chat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Chat::create([
            'conversation_id' => '1',
            'sender_id' => '1',
            'receiver_id' => '2',
            'type' => 'text',
            'message' => 'Hi'
        ]);
    }
}
