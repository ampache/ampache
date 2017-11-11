<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SongTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('songs')->delete();
    }
}
