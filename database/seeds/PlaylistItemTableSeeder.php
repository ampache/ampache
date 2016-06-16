<?php

use Illuminate\Database\Seeder;

class PlaylistItemTableSeeder extends Seeder {

    public function run()
    {
        DB::table('playlist_items')->delete();
        
        
    }
}
