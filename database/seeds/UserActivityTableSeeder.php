<?php

use Illuminate\Database\Seeder;

class UserActivityTableSeeder extends Seeder {

    public function run()
    {
        DB::table('user_activities')->delete();
        
        
    }
}
