<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Content;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            UserSeeder::class,
            ContentSeeder::class,
            CasesSeeder::class,
            AvailabilitySeeder::class,
            FeedbackSeeder::class,
            SubscriptionSeeder::class,
            NotificationSeeder::class,
            ConversationSeeder::class,
            ChatSeeder::class,
        ]);
    }
}
