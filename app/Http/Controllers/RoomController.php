<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Room;
use App\Http\Tasks\RoomTask;

class RoomController extends Controller {

  public function index(Request $request) {
    $rooms = Room::all();

    return response()->json($rooms);
  }

  public function show(Request $request, $roomId) {
    $room = Room::findOrFail($roomId);
    $room->type;

    return response()->json($room);
  }

  public function getRoomsMaintenance() {
    $rooms = Room::where('state', 'mantenimiento')
    ->get();

    return response()->json($rooms);
  }

  public function getRoomsDisabled() {
    $rooms = Room::where('state', 'desahabilitada')
    ->get();

    return response()->json($rooms);
  }


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

  public function update(Request $request, $roomId) {
    $inputData = $request->only('code_number', 'type_id');

    $room = Room::findOrFail($roomId);

    if($room->update($inputData)) {
        return response()->json($room);
    } else {
        return response()->validation_error($room->errors());
    } 
  }

  public function availableDatesRooms(Request $request, RoomTask $roomTask, $startDate, $endDate, $time) { 
    $roomTask->setData($startDate, $time, $endDate);

    if(!$roomTask->isValidDataQuery()) {
        return response()->validation_error($roomTask->getMessage());
    }

    $rooms = $roomTask->getAvailableDateRoom();

    return response()->json($rooms);
  }

  public function availableHourRooms(Request $request, RoomTask $roomTask, $startDate, $starTime, $endTime) {
    $roomTask->setData($startDate, $starTime, null, $endTime);

    if(!$roomTask->isValidDataQuery()) {
        return response()->validation_error($roomTask->getMessage());
    }

    $rooms = $roomTask->getAvailableHourRoom();

    return response()->json($rooms);
  }

  public function availableAddHourRooms(Request $request, RoomTask $roomTask, $startDate, $starTime, $endTime) {
    $roomTask->setData($startDate, $starTime, null, $endTime);

    $rooms = $roomTask->getAvailableHourRoom();

    return response()->json($rooms);
  }

  public function disableRoom(Request $request, $roomId) {
    $room = Room::findOrFail($roomId);

    if($room->hasRental()->count() > 0) {
        return response()->validation_error('La habitación no se pude desabilitar si tiene hospedaje vigente o esta reservada');
    }

    $room->state = 'desahabilitada';
    $room->save();

    return response()->json(['message' => 'La habitacion ha sido desahabilitada']);
  }

  public function enableRoom(Request $request, $roomId) {
    $room = Room::findOrFail($roomId);

    if($room->state != 'desahabilitada') {
        return response()->validation_error('Esta habitación no esta desahabilitada');
    }

    $room->state = 'disponible';
    $room->save();

    return response()->json(['message' => 'La habitacion ha sido habilitada']);
  }

  public function delete(Request $request, $roomId) {
    $room = Room::findOrFail($roomId);

    if($room->hasRental()->count() > 0) {
        return response()->validation_error('La habitación no puede ser borrada si tiene hospedaje vigente o esta reservada');
    }

    $room->delete();

    return response()->json(['message' => 'La  habitacion ha sido borrada']);
  }
}
