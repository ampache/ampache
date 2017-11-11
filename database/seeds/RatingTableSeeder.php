<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatingTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('ratings')->delete();
    }
}
