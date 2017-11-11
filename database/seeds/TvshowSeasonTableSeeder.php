<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TvshowSeasonTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('tvshow_seasons')->delete();
    }
}
