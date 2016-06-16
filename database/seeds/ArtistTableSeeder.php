<?php

use Illuminate\Database\Seeder;

class ArtistTableSeeder extends Seeder {

    public function run()
    {
        DB::table('artists')->delete();
        
        
    }
}
