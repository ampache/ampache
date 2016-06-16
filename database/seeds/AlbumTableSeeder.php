<?php

use Illuminate\Database\Seeder;

class AlbumTableSeeder extends Seeder {

    public function run()
    {
        DB::table('albums')->delete();
        
        
    }
}
