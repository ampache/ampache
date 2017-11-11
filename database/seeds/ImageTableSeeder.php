<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImageTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('images')->delete();
    }
}
