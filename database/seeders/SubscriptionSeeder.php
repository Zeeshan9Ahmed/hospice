<?php

namespace Database\Seeders;

use App\Models\Subscription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Subscription::create([
            'subscription_plan' => 'Silver',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit hendrerit erat consequat. Fusce dui dui, faucibus id ipsum ac, interdum pretium libero. Vivamus aliquam ipsum vel leo varius aliquet.',
            'price' => '30.00',
            'platform_fee' => '0.05',
            'total_amount'   => '30.05',
        ]);

        Subscription::create([
            'subscription_plan' => 'Gold',
            'description' => 'Consectetur adipiscing elit hendrerit erat consequat. Vivamus aliquam ipsum vel leo varius aliquet.',
            'price' => '35.00',
            'platform_fee' => '0.05',
            'total_amount'   => '35.05',
        ]);

        Subscription::create([
            'subscription_plan' => 'Platinum',
            'description' => 'hendrerit erat consequat. Vivamus aliquam ipsum vel leo varius aliquet.',
            'price' => '40.00',
            'platform_fee' => '0.05',
            'total_amount'   => '40.05',
        ]);
    }

}
