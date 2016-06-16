<?php

use Illuminate\Database\Seeder;

class BroadcastTableSeeder extends Seeder {

    public function run()
    {
        DB::table('broadcasts')->delete();
        
        
    }
}
