<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
      factory('App\Models\User', 3)->create()->each(function ($user) {
        $role = $user->id;
        $user->roles()->attach($role);
      });
    }
}
