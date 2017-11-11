<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PodcastEpisodeTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('podcast_episodes')->delete();
    }
}
