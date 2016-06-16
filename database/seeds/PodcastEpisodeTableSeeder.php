<?php

use Illuminate\Database\Seeder;

class PodcastEpisodeTableSeeder extends Seeder {

    public function run()
    {
        DB::table('podcast_episodes')->delete();
        
        
    }
}
