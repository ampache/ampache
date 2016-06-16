<?php

use Illuminate\Database\Seeder;

class PersonalVideoTableSeeder extends Seeder {

    public function run()
    {
        DB::table('personal_videos')->delete();
        
        
    }
}
