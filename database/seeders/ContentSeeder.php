<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Content::create([
            'type' => 'terms-and-condition',
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras in mi sit amet dui gravida malesuada sed id arcu. Maecenas non pulvinar justo. Curabitur ullamcorper nisi at justo feugiat pharetra. Nunc hendrerit viverra nunc a malesuada. Aliquam finibus semper leo, at euismod tellus feugiat vel. Cras congue, dui eu accumsan consequat, tellus justo maximus velit, in dignissim augue nisi quis ligula. In placerat enim et ipsum facilisis, vitae aliquet ligula convallis. Proin dictum ac quam non blandit. Aliquam nec risus egestas, semper lorem vel, facilisis lacus. Integer gravida viverra urna. Phasellus finibus erat id erat pulvinar bibendum. Integer justo diam, mollis sit amet porttitor a, maximus non nisl. Pellentesque pharetra nunc semper augue convallis, varius hendrerit erat consequat. Fusce dui dui, faucibus id ipsum ac, interdum pretium libero. Vivamus aliquam ipsum vel leo varius aliquet.'
        ]);

        Content::create([
            'type' => 'privacy-policy',
            'description' => 'Praesent nisl tortor, rhoncus aliquam erat at, condimentum laoreet risus. Aliquam erat volutpat. Etiam ullamcorper at purus et volutpat. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam nibh felis, iaculis ac sapien ut, tincidunt feugiat purus. Donec venenatis lorem vel justo aliquet, et tempus mauris ultrices. Mauris rhoncus faucibus odio, ut feugiat enim aliquam sit amet. Ut id vehicula mi, in vulputate orci. Mauris venenatis diam non est dignissim convallis. Praesent nec vehicula est, fermentum feugiat orci. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae;'
        ]);
    }
}
