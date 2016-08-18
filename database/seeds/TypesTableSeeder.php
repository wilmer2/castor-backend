<?php

use Illuminate\Database\Seeder;
use App\Models\Room;

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
