<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArtistTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('artists')->delete();
    }
}
