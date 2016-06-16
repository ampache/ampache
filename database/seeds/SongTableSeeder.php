<?php

use Illuminate\Database\Seeder;

class SongTableSeeder extends Seeder {

    public function run()
    {
        DB::table('songs')->delete();
        
        
    }
}
