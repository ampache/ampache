<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('access_list')->insert([
            'name' => 'DEFAULTv4',
            'start' => '00:00:00:00',
            'end' => 'ff:ff:ff:ff',
            'level' => 75,
            'type' => 'interface',
            'user' => -1,
            'enabled' => 1,
        ]);
        DB::table('access_list')->insert([
            'name' => 'DEFAULTv4',
            'start' => '00:00:00:00',
            'end' => 'ff:ff:ff:ff',
            'level' => 75,
            'type' => 'stream',
            'user' => -1,
            'enabled' => 1,
        ]);
        DB::table('access_list')->insert([
            'name' => 'DEFAULTv4',
            'start' => '00:00:00:00',
            'end' => 'ff:ff:ff:ff',
            'level' => 75,
            'type' => 'rpc',
            'user' => -1,
            'enabled' => 1,
        ]);
        
        DB::table('access_list')->insert([
            'name' => 'DEFAULTv6',
            'start' => '0000:0000:0000:0000',
            'end' => 'ffff:ffff:ffff:ffff',
            'level' => 75,
            'type' => 'interface',
            'user' => -1,
            'enabled' => 1,
        ]);
        
        DB::table('access_list')->insert([
            'name' => 'DEFAULTv6',
            'start' => '0000:0000:0000:0000',
            'end' => 'ffff:ffff:ffff:ffff',
            'level' => 75,
            'type' => 'stream',
            'user' => -1,
            'enabled' => 1,
        ]);

        DB::table('access_list')->insert([
            'name' => 'DEFAULTv6',
            'start' => '0000:0000:0000:0000',
            'end' => 'ffff:ffff:ffff:ffff',
            'level' => 75,
            'type' => 'rpc',
            'user' => -1,
            'enabled' => 1,
        ]);
    }
}
