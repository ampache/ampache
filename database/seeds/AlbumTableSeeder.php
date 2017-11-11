<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlbumTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('albums')->delete();
    }
}
