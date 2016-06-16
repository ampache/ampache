<?php

use Illuminate\Database\Seeder;

class LabelTableSeeder extends Seeder {

    public function run()
    {
        DB::table('labels')->delete();
        
        
    }
}
