<?php

use Illuminate\Database\Seeder;

class TypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
      factory('App\Models\Type', 'basic')->create();
      factory('App\Models\Type', 'special')->create();

    }
}
