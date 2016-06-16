<?php

use Illuminate\Database\Seeder;

class PodcastTableSeeder extends Seeder {

    public function run()
    {
        DB::table('podcasts')->delete();
        
        
    }
}
