<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Room;

class RoomController extends Controller {

  public function store(Request $request) {
    $inputData = $request->all();

    $newRoom = new Room($inputData);
    $newRoom->state = 'disponible';

    if($newRoom->save()) {
        return response()->json($newRoom);
    } else {
        return response()->validation_error($newRoom->errors());
    }
  }
}
