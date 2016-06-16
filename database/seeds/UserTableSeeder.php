<?php

use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder {

    public function run()
    {
        DB::table('users')->delete();

        for($i = 0; $i < 10; ++$i)
        {
            $date = $this->randDate();
            DB::table('users')->insert([
                'fullname' => 'Nom' . $i,
                'username' => 'user' . $i,
                'email' => 'email' . $i . '@dummy.com',
                'password' => hash('sha256', 'password' . $i),
                'access' => ($i == 0) ? 100 : 25,
                'created_at' => $date,
                'updated_at' => $date
            ]);
        }
    }
}
