<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaylistItemTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('playlist_items')->delete();
    }
}
