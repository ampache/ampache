<?php

use Illuminate\Database\Seeder;

class ShoutTableSeeder extends Seeder {

    public function run()
    {
        DB::table('shouts')->delete();
        
        
    }
}
