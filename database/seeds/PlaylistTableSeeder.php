<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaylistTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('playlists')->delete();
    }
}
