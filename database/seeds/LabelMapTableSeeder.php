<?php

use Illuminate\Database\Seeder;

class LabelMapTableSeeder extends Seeder {

    public function run()
    {
        DB::table('label_maps')->delete();
        
        
    }
}
