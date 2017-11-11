<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovieTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('movies')->delete();
    }
}
