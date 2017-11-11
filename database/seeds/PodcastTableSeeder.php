<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PodcastTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('podcasts')->delete();
    }
}
