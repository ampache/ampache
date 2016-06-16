<?php

use Illuminate\Database\Seeder;

class TvshowSeasonTableSeeder extends Seeder {

    public function run()
    {
        DB::table('tvshow_seasons')->delete();
        
        
    }
}
