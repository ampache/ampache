<?php

use Illuminate\Database\Seeder;

class TmpPlaylistTableSeeder extends Seeder {

    public function run()
    {
        DB::table('tmp_playlists')->delete();
        
        
    }
}
