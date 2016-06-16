<?php

use Illuminate\Database\Seeder;

class RatingTableSeeder extends Seeder {

    public function run()
    {
        DB::table('ratings')->delete();
        
        
    }
}
