<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Client;
use App\Models\Rental;
use App\Models\Room;
use App\Http\Tasks\RoomTask;

class RentalController extends Controller {

  public function store(Request $request) {
    if($request->has('clientId')) {
        $client = Client::findOrFail($request->get('clientId'));
    } else {
        $client = Client::searchForIdentityCard($request->get('identity_card'));
    }

    $inputData = $request->all();
    $newRental = new Rental($inputData);

    $newRental->client_id = $client->id;

    if($newRental->save()) {
        $newRental->rooms()->attach($request->get('room_ids'));
        
        return response()->json($newRental);
    } else {
        return response()->validation_error($newRental->errors());
    }
  }

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
        $newReservation->rooms()->attach($request->get('room_ids'));

        return response()->json($newReservation);
    } else {
        return response()->validation_error($newReservation->errors());
    }
  }

  public function updateReservationForHour(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if(!$rental->reservation) {
        return response()->validation_error('No se puede editar hospedaje');
    }

    $rental->type = 'hours';
    $rental->departure_date = null;

    $inputData = $request->only('arrival_date', 'arrival_time', 'departure_time', 'room_ids');
     
    if($rental->update($inputData)) {
        //Se obtiene rooms_ids ya que al validar es purgado
        $rental->rooms()->sync($request->get('room_ids'));  

        return response()->json($rental);
    } else {
        return response()->validation_error($rental->errors());
    }
    
  }

  public function updateReservationForDate(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if(!$rental->reservation) {
        return response()->validation_error('No se puede editar hospedaje');
    }

    $rental->type = 'days';
    $rental->departure_time = createHour('12:00');

    $inputData = $request->only('arrival_date', 'arrival_time', 'departure_date', 'room_ids');

    if($rental->update($inputData)) {
        //Se obtiene rooms_ids ya que al validar es purgado
        $rental->rooms()->sync($request->get('room_ids'));

        return response()->json($rental);
    } else {
        return response()->validation_error($rental->errors());
    }

  }

  public function getAvailableDateRoom(Request $request, RoomTask $roomTask,$rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $roomsId = $rental->getRoomsId();
      
    $roomTask->setData(
       $request->get('arrival_date'),
       $request->get('arrival_time'),
       $request->get('departure_date')
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

    $roomTask->setData(
       $request->get('arrival_date'),
       $request->get('arrival_time'),
       null,
       $request->get('departure_time')
    );

    if(!$roomTask->isValidDataQuery()) {
        return response()->validation_error($roomTask->getMessage());
    }

    $rooms = $roomTask->getRoomHourReservation($rental->id ,$roomsId);

    return response()->json($rooms);
  }
}