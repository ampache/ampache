<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FavoriteTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('favorites')->delete();
    }
}
