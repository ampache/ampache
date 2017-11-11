<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TvshowEpisodeTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('tvshow_episodes')->delete();
    }
}
