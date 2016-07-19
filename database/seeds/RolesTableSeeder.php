<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
      factory('App\Models\Role', 'super')->create();
      factory('App\Models\Role', 'admin')->create();
      factory('App\Models\Role', 'user')->create();
    }
}
