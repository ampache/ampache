<?php

use Illuminate\Database\Seeder;

class TvshowTableSeeder extends Seeder {

    public function run()
    {
        DB::table('tvshows')->delete();
        
        
    }
}
