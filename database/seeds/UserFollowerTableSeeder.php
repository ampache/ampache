<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserFollowerTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('user_followers')->delete();
    }
}
