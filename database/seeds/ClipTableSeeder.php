<?php

use Illuminate\Database\Seeder;

class ClipTableSeeder extends Seeder {

    public function run()
    {
        DB::table('clips')->delete();
        
        
    }
}
