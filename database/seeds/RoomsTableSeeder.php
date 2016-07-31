<?php

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
      foreach(range(1, 6) as $index) {
        $codeNumber = 'hb-'.$index;

        if($index <= 3) {
          $typeId = 1;
        } else {
          $typeId = 2;
        }

        Room::create([
          'code_number' => $codeNumber,
          'type_id' => $typeId,
          'state' => 'disponible'

        ]);
      }
    }
}
