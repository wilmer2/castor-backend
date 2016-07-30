<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Client;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Record;
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
    $newRental->addDateTime();

    if($newRental->save()) {
        $newRental->rooms()->attach($inputData['room_ids']);

        $newRental->moveDispatch();   
        return response()->json($newRental);
    } else {
        return response()->validation_error($newRental->errors());
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

  public function checkoutRoom(Request $request, $rentalId, $roomId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();

    if($rental->type == 'hours' || $rental->reservation) {
        return response()->validation_error('Los hospedajes por horas o en reservacion no pueden marcar salida');
    }

    if($rental->type == 'days' && $date == $rental->arrival_date) {
        return response()->validation_error('La habitación debe tener al menos un día para dar salida');
    }

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }
      
    $room = $rental->findRoom($roomId);

    if(!$room || $room->check_out != null) {
        return response()->validation_error('La habitación no existe o ya tiene salida');
    }

    $room->pivot->check_out = $date;
    $room->pivot->save();
    $rental->moveDispatch();

    return response()->json(['message' => 'Salida de habitación confirmada']);
  }

  public function checkout(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();

    if($rental->checkout) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    if($rental->reservation) {
        return response()->validation_error('La reservacion no ha sido confirmada');
    }

    if($rental->arrival_date == $date && $rental->type == 'days') {
        return response()->validation_error('El hospedaje debe tener al menos un día para dar salida');
    }

    if($rental->type == 'days' && $date < $rental->departure_date) {
        $rental->checkout_date = $date;
    }

    $rental->checkout = 1;
    $rental->forceSave();
    $rental->moveDispatch();

    return response()->json($rental);
    
  }

  public function removeRoom(Request $request, $rentalId, $roomId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    if($rental->rooms()->count() == 1) {
        return response()->validation_error('El hospedaje debe tener al menos una habitación');
    }
      
    $room = $rental->findRoom($roomId);

    if(!$room || $room->check_out != null) {
        return response()->validation_error('Habitación no puede ser removida');
    }

    $rental->rooms()->detach($roomId);
    $rental->moveDispatch();

    return response()->json(['message' => 'Habitación a sido removida']);

  }

  public function addRooms(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    if($rental->reservation && $rental->arrival_date < $date) {
        return response()->validation_error('Debe confirmar reservación');
    }

    $inputData = $request->only('room_ids', 'discount');

    if($rental->update($inputData)) {
        $rental->rooms()->sync($inputData['room_ids'], false);
        $rental->moveDispatch();

        return response()->json($rental);
    } else {
        return response()->validation_error($rental->errors());
    }
  }

  
}