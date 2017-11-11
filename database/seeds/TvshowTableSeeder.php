<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TvshowTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('tvshows')->delete();
    }
}
