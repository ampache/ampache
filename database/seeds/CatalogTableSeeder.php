<?php

use Illuminate\Database\Seeder;

class CatalogTableSeeder extends Seeder {

    public function run()
    {
        DB::table('catalogs')->delete();

        DB::table('catalogs')->insert([
            'name' => 'Music',
            'catalog_type' => 'local',
            'rename_pattern' => '%T - %t',
            'sort_pattern' => '%a/%A',
            'gather_types' => 'music'
        ]);
        
        DB::table('catalogs')->insert([
            'name' => 'Movie',
            'catalog_type' => 'local',
            'rename_pattern' => '%T - %t',
            'sort_pattern' => '%a/%A',
            'gather_types' => 'movie'
        ]);
        
        DB::table('catalogs')->insert([
            'name' => 'Podcast',
            'catalog_type' => 'local',
            'rename_pattern' => '%T - %t',
            'sort_pattern' => '%a/%A',
            'gather_types' => 'podcast'
        ]);
    }
}
