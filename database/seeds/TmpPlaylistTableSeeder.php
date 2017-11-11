<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TmpPlaylistTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('tmp_playlists')->delete();
    }
}
