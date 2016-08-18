<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
      $this->call(SettingsTableSeeder::class);
      $this->call(RolesTableSeeder::class);
      $this->call(UsersTableSeeder::class);
      $this->call(TypesTableSeeder::class);
      $this->call(RoomsTableSeeder::class);
      $this->call(ClientsTableSeeder::class);
    }
    
}
