<?php

namespace Database\Seeders;

use App\Models\Conversation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Conversation::create([
            'sender_id' => '1',
            'receiver_id' => '2',
            'type' => 'text',
            'last_message' => 'Hi'
        ]);
    }
}
