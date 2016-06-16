<?php

use Illuminate\Database\Seeder;

class UserFollowerTableSeeder extends Seeder {

    public function run()
    {
        DB::table('user_followers')->delete();
        
        
    }
}
