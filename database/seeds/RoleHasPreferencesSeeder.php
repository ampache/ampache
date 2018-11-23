<?php

use Illuminate\Database\Seeder;
use App\Models\Preference;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

class RoleHasPreferencesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('role_has_preferences')->insert([
            ['preference_id' => 4, 'role_id' => 1],
            ['preference_id' => 5, 'role_id' => 1],
            ['preference_id' => 6, 'role_id' => 1],
            ['preference_id' => 8, 'role_id' => 1],
            ['preference_id' => 9, 'role_id' => 1],
            ['preference_id' => 11, 'role_id' => 1],
            ['preference_id' => 12, 'role_id' => 1],
            ['preference_id' => 13, 'role_id' => 1],
            ['preference_id' => 14, 'role_id' => 1],
            ['preference_id' => 15, 'role_id' => 1],
            ['preference_id' => 18, 'role_id' => 1],
            ['preference_id' => 29, 'role_id' => 1],
            ['preference_id' => 39, 'role_id' => 1],
            ['preference_id' => 40, 'role_id' => 1],
            ['preference_id' => 41, 'role_id' => 1],
            ['preference_id' => 42, 'role_id' => 1],
            ['preference_id' => 43, 'role_id' => 1],
            ['preference_id' => 45, 'role_id' => 1],
            ['preference_id' => 46, 'role_id' => 1],
            ['preference_id' => 47, 'role_id' => 1],
            ['preference_id' => 55, 'role_id' => 1],
            ['preference_id' => 62, 'role_id' => 1],
            ['preference_id' => 63, 'role_id' => 1],
            ['preference_id' => 64, 'role_id' => 1],
            ['preference_id' => 65, 'role_id' => 1],
            ['preference_id' => 83, 'role_id' => 1],
            ['preference_id' => 84, 'role_id' => 1],
            ['preference_id' => 86, 'role_id' => 1],
            ['preference_id' => 87, 'role_id' => 1],
            ['preference_id' => 88, 'role_id' => 1],
            ['preference_id' => 89, 'role_id' => 1],
            ['preference_id' => 90, 'role_id' => 1],
            ['preference_id' => 91, 'role_id' => 1],
            ['preference_id' => 92, 'role_id' => 1],
        ]);

        DB::table('role_has_preferences')->insert([
            ['preference_id' => 1, 'role_id' => 2],
            ['preference_id' => 2, 'role_id' => 2],
            ['preference_id' => 56, 'role_id' => 2],
            ['preference_id' => 57, 'role_id' => 2],
            ['preference_id' => 58, 'role_id' => 2],
            ['preference_id' => 59, 'role_id' => 2],
            ['preference_id' => 60, 'role_id' => 2],
            ['preference_id' => 61, 'role_id' => 2],
            ['preference_id' => 79, 'role_id' => 2],
            ['preference_id' => 80, 'role_id' => 2],
            ['preference_id' => 81, 'role_id' => 2],
            ['preference_id' => 82, 'role_id' => 2],
            ['preference_id' => 95, 'role_id' => 2],
            ['preference_id' => 96, 'role_id' => 2],
            ['preference_id' => 97, 'role_id' => 2],
            ['preference_id' => 25, 'role_id' => 3],
            ['preference_id' => 3, 'role_id' => 4],
            ['preference_id' => 7, 'role_id' => 4],
            ['preference_id' => 16, 'role_id' => 4],
            ['preference_id' => 20, 'role_id' => 4],
            ['preference_id' => 22, 'role_id' => 4],
            ['preference_id' => 24, 'role_id' => 4],
            ['preference_id' => 26, 'role_id' => 4],
            ['preference_id' => 27, 'role_id' => 4],
            ['preference_id' => 28, 'role_id' => 4],
            ['preference_id' => 31, 'role_id' => 4],
            ['preference_id' => 32, 'role_id' => 4],
            ['preference_id' => 33, 'role_id' => 4],
            ['preference_id' => 34, 'role_id' => 4],
            ['preference_id' => 35, 'role_id' => 4],
            ['preference_id' => 36, 'role_id' => 4],
            ['preference_id' => 37, 'role_id' => 4],
            ['preference_id' => 38, 'role_id' => 4],
            ['preference_id' => 48, 'role_id' => 4],
            ['preference_id' => 49, 'role_id' => 4],
            ['preference_id' => 50, 'role_id' => 4],
            ['preference_id' => 51, 'role_id' => 4],
            ['preference_id' => 52, 'role_id' => 4],
            ['preference_id' => 53, 'role_id' => 4],
            ['preference_id' => 54, 'role_id' => 4],
            ['preference_id' => 66, 'role_id' => 4],
            ['preference_id' => 67, 'role_id' => 4],
            ['preference_id' => 68, 'role_id' => 4],
            ['preference_id' => 69, 'role_id' => 4],
            ['preference_id' => 70, 'role_id' => 4],
            ['preference_id' => 71, 'role_id' => 4],
            ['preference_id' => 72, 'role_id' => 4],
            ['preference_id' => 73, 'role_id' => 4],
            ['preference_id' => 74, 'role_id' => 4],
            ['preference_id' => 75, 'role_id' => 4],
            ['preference_id' => 76, 'role_id' => 4],
            ['preference_id' => 77, 'role_id' => 4],
            ['preference_id' => 78, 'role_id' => 4],
            ['preference_id' => 93, 'role_id' => 5],
            ['preference_id' => 94, 'role_id' => 5],
            ['preference_id' => 98, 'role_id' => 5],
            ['preference_id' => 99, 'role_id' => 5],
            ['preference_id' => 17, 'role_id' => 5],
            ['preference_id' => 19, 'role_id' => 5],
            ['preference_id' => 21, 'role_id' => 5],
            ['preference_id' => 85, 'role_id' => 5],
        ]);
    }
}
