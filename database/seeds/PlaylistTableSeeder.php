<?php

use Illuminate\Database\Seeder;

class PlaylistTableSeeder extends Seeder {

    public function run()
    {
        DB::table('playlists')->delete();
        
        
    }
}
