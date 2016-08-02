<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Client;
use App\Models\Rental;
use App\Http\Tasks\RoomTask;

class ReservationController extends Controller {

  public function addReservation(Request $request) {
    if($request->has('clientId')) {
        $client = Client::findOrFail($request->get('clientId'));
    } else {
        $client = Client::searchForIdentityCard($request->get('identity_card'));
    }

    $inputData = $request->all();
    $newReservation = new Rental($inputData);

    if(isset($inputData['pay'])) {
        $newReservation->state = 'conciliado';
    } else {
        $newReservation->state = 'por pagar';
    }

    $newReservation->client_id = $client->id;
    $newReservation->reservation = 1;

    if($newReservation->save()) {
        $newReservation->rooms()->attach($inputData['room_ids']);
        $newReservation->registerRecord();

        $newReservation->moveDispatch();
        return response()->json($newReservation);
    } else {
        return response()->validation_error($newReservation->errors());
    }
  }

  public function updateReservationForHour(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if(!$rental->reservation) {
        return response()->validation_error('El hospedaje no se puede editar');
    }

    $rental->type = 'hours';
    $rental->departure_date = null;

    $inputData = $request->only('arrival_date', 'arrival_time', 'departure_time', 'room_ids');
     
    if($rental->update($inputData)) {
        $rental->rooms()->sync($inputData['room_ids']);
        $rental->registerRecord();

        $rental->moveDispatch();
        return response()->json($rental);
    } else {
        return response()->validation_error($rental->errors());
    }
    
  }

  public function updateReservationForDate(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if(!$rental->reservation) {
        return response()->validation_error('El hospedaje no se puede editar');
    }

    $rental->type = 'days';
    $rental->departure_time = createHour('12:00:00');

    $inputData = $request->only('arrival_date', 'arrival_time', 'departure_date', 'room_ids');

    if($rental->update($inputData)) {
        $rental->rooms()->sync($inputData['room_ids']);
        $rental->registerRecord();
        
        $rental->moveDispatch();
        return response()->json($rental);
    } else {
        return response()->validation_error($rental->errors());
    }

  }

  public function getAvailableDateRoom(Request $request, RoomTask $roomTask,$rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $roomsId = $rental->getRoomsId();
    $data = $request->only('arrival_date', 'arrival_time', 'departure_date');
      
    $roomTask->setData(
       $data['arrival_date'],
       $data['arrival_time'],
       $data['departure_date']
    );

    if(!$roomTask->isValidDataQuery()) {
        return response()->validation_error($roomTask->getMessage());
    }

    $rooms = $roomTask->getRoomDateReservation($rental->id, $roomsId);
    return response()->json($rooms);
  }

  public function getAvailableHourRoom(Request $request, RoomTask $roomTask, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $roomsId = $rental->getRoomsId();
    $data = $request->only('arrival_date', 'arrival_time', 'departure_time');

    $roomTask->setData(
       $data['arrival_date'],
       $data['arrival_time'],
       null,
       $data['departure_time']
    );

    if(!$roomTask->isValidDataQuery()) {
        return response()->validation_error($roomTask->getMessage());
    }

    $rooms = $roomTask->getRoomHourReservation($rental->id ,$roomsId);
    return response()->json($rooms);
  }
}
