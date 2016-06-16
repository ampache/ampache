<?php

use Illuminate\Database\Seeder;

class TvshowEpisodeTableSeeder extends Seeder {

    public function run()
    {
        DB::table('tvshow_episodes')->delete();
        
        
    }
}
