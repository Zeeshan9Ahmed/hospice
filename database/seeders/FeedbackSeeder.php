<?php

namespace Database\Seeders;

use App\Models\Feedback;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Feedback::create([
            'user_id' => '2',
            'post_id' => '1',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras in mi sit amet dui gravida malesuada sed id arcu. Maecenas non pulvinar justo. Curabitur ullamcorper nisi at justo feugiat pharetra. Nunc hendrerit viverra nunc a malesuada. Aliquam finibus semper leo, at euismod tellus feugiat vel. Cras congue, dui eu accumsan consequat, tellus justo maximus velit, in dignissim augue nisi quis ligula. In placerat enim et ipsum facilisis, vitae aliquet ligula convallis. Proin dictum ac quam non blandit. Aliquam nec risus egestas, semper lorem vel, facilisis lacus. Integer gravida viverra urna. Phasellus finibus erat id erat pulvinar bibendum. Integer justo diam, mollis sit amet porttitor a, maximus non nisl. Pellentesque pharetra nunc semper augue convallis, varius hendrerit erat consequat.',
            'rating' => '5',
        ]);
    }
}
