<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Client;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Record;
use App\Http\Tasks\RoomTask;
use App\Validators\RentalValidator;

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
        $newRental->registerRecord();

        $newRental->moveDispatch();   
        return response()->json($newRental);
    } else {
        return response()->validation_error($newRental->errors());
    }
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
    
    if(!$room) {
        return response()->validation_error('Habitación no encontrada');
    }

    if($room->check_out != null) {
        return response()->validation_error('Habitación no puede ser removida');
    }
    
    $room->state = 'disponible';
    $room->save();

    $rental->rooms()->detach($roomId);
    $rental->moveDispatch();

    return response()->json(['message' => 'Habitación a sido removida']);

  }

  public function changeRoom(Request $request, $rentalId, $roomId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();
    
    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    if($rental->reservationExpired()) {
        return response()->validation_error('La reservación ya expiro');
    }

    $room = $rental->findRoom($roomId);

    if(!$room) {
        return response()->validation_error('Habitación no encontrada');
    }

    $newRoom = Room::find($request->get('room_id'));

    if(
        $rental->reservation || 
        $rental->arrival_date == $date || 
        $rental->type == 'hours'
    ) { 
        $rental->rooms()->detach($room->id);
        $sync = [$newRoom->id];
    } else {
        $room->pivot->check_out = $date;
        $room->pivot->save();
        
        $sync = [$newRoom->id => ['check_in' => $date]];
    }
    
    $room->state = 'limpieza';
    $room->save();

    $rental->rooms()->sync($sync, false);
    $rental->moveDispatch();

    return response()->json(['message' => 'Habitación a sido cambiada']);
  }

  public function addRoomsDate(Request $request, RentalValidator $rentalValidator, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();
    $hour = currentHour();

    if($rental->isCheckout() || $rental->type == 'hours') {
        return response()->validation_error('Este hospedaje no puede agregar habitaciones');
    }

    if($rental->reservationExpired()) {
        return response()->validation_error('La reservación ya expiro');
    }

    if($rental->departure_date == $date) {
        return response()->validation_error('No puede agregar habitaciones en la fecha de salida');
    }

    $roomIds = $request->get('room_ids');

    if($rental->reservation && $rental->arrival_date >= $date) {
        $date = $rental->arrival_date;
        $hour = $rental->arrival_time;
    }

    $validRooms = $rentalValidator->isValidRoomDate($roomIds, $date, $rental->departure_date, $hour);

    if(!$validRooms) {
        return response()->validation_error('Alguna de las habitaciones no esta disponible');
    }

    $sync = $roomIds;

    if(!$rental->reservation && $rental->arrival_date < $date) {
        $sync = syncData($roomIds, $date);
    }

    $rental->rooms()->sync($sync, false);
    $rental->moveDispatch();

    return response()->json(['message' => 'Habitaciones registradas']);
  }

  public function addRoomsHour(Request $request, RentalValidator $rentalValidator, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->isCheckout() || $rental->type == 'days') {
        return response()->validation_error('Este hospedaje no puede agregar habitaciones');
    }

    if($rental->reservationExpired()) {
        return response()->validation_error('La reservación ya expiro');
    }

    if($rental->isTimeout()) {
        return response()->validation_error('Hora de hospedaje ya termino');
    }

    $roomIds = $request->get('room_ids');

    $validRooms = $rentalValidator->isValidRoomHour(
        $roomIds,
        $rental->arrival_time,
        $rental->departure_time,
        $rental->arrival_date,
        $rental->departure_date
    );

    if(!$validRooms) {
        return response()->validation_error('Alguna de las habitaciones no esta disponible');
    }

    $rental->rooms()->sync($roomIds, false);
    $rental->moveDispatch();

    return response()->json(['message' => 'Habitaciones registradas']);
  }

  public function checkoutRoomDate(Request $request, $rentalId, $roomId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    if($rental->type == 'hours') {
        return response()->validation_error('El hospedaje debe ser por día');
    }

    if($rental->reservation) {
        return response()->validation_error('Debe confirmar reservación');
    }

    $room = $rental->findRoom($roomId);

    if(!$room) {
        return response()->validation_error('Habitación no encontrada');
    }

    if($room->pivot->check_out != null) {
        return response()->validation_error('Habitación ya tiene salida');
    }

    if(
        $rental->arrival_date == $date || 
        $room->pivot->check_in != null &&  
        $room->pivot->check_in == $date
    ) {
        return response()->validation_error('La habitación debe tener al menos un día para dar salida');
    }

    $room->pivot->check_out = $date;
    $room->pivot->save();
    $room->state = 'limpieza';
    $room->save();

    $rental->moveDispatch();
    $rental->confirmCheckoutRoom();

    return response()->json(['message' => 'Salida de habitación confirmada']);
  }

  public function checkoutRoomHour(Request $request, $rentalId, $roomId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    if($rental->type == 'days') {
        return response()->validation_error('El hospedaje debe ser por horas');
    }

    if($rental->reservation) {
        return response()->validation_error('Debe confirmar reservación');
    }

    $room = $rental->findRoom($roomId);

    if(!$room) {
        return response()->validation_error('Habitación no encontrada');
    }

    if($room->pivot->check_out != null) {
        return response()->validation_error('Habitación ya tiene salida');
    }

    $room->pivot->check_timeout = $rental->departure_time;

    if($rental->departure_date != null) {
        $room->pivot->check_out = $rental->departure_date;
    } else {
        $room->pivot->check_out = $rental->arrival_date;
    }

    $room->pivot->save();
    $room->state = 'limpieza';
    $room->save();

    $rental->confirmCheckoutRoom();

    return response()->json(['message' => 'Salida de habitación confirmada']);
  }

  /*public function renovateHour(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    $inputData = $request->only('renovate_hour', 'room_ids', 'discount');

    if($rental->update($inputData)) {
        $newRecord = new Record();
        $rental->rooms()->sync($inputData['room_ids'], false);
        $rental->setRecord($newRecord);
        $rental->moveDispatch();

        return response()->json(['message' => 'Habitación renovada']);
    } else {
        return response()->validation_error($rental->errors());
    }
  }*/

 
  /*public function checkoutRoom(Request $request, $rentalId, $roomId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();

    if($rental->type == 'hours' || $rental->reservation) {
        return response()->validation_error('Los hospedajes por horas o en reservacion no pueden marcar salida');
    }

    if($date == $rental->arrival_date) {
        return response()->validation_error('La habitación debe tener al menos un día para dar salida');
    }

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }
      
    $room = $rental->findRoom($roomId);

    if(!$room || $room->check_out != null) {
        return response()->validation_error('La habitación no existe o ya tiene salida');
    }
     
    $room->state = 'limpieza';
    $room->save();

    $room->pivot->check_out = $date;
    $room->pivot->save();
    $rental->moveDispatch();

    return response()->json(['message' => 'Salida de habitación confirmada']);
  }*/

  /*public function checkout(Request $request, $rentalId) {
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
    
  }*/


  /*public function renovateHour(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje no puede ser renovado');
    }

    if($rental->reservation) {
        return response()->validation_error('Debe confimar reservación');
    }

    $inputData = $request->only('renovate_hour', 'room_ids', 'discount');

    if($rental->update($inputData)) {
        $rental->rooms()->sync($inputData['room_ids']);
        $rental->moveDispatch();

        $newRecord = new Record();
        $newRecord->departure_time = $rental->departure_time;
        $newRecord->departure_date = $rental->departure_date;
        $newRecord->type = $rental->type;

        $rental->records()->save($newRecord);

        return response()->json($rental);
    } else {
        return response()->validation_error($rental->errors());
    }
  }*/

  /*public function renovateDate(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje no puede ser renovado');
    }

    if($rental->reservation) {
        return response()->validation_error('Debe confimar reservación');
    }

    $inputData = $request->only('departure_date', 'room_ids');

    $roomIds = $request->get('room_ids');

    $newRecord = new Record();
    $newRecord->setData($rental);

    $rental->setOldDeparture();
    $rental->type = 'days';
    $rental->room_ids = $roomIds;
    $rental->departure_date = $request->get('departure_date');

    if($rental->save()) {
        $roomIdsCheckOut = $rental->getRoomsIdCheckout();

        if(count($roomIdsCheckOut) > 0) {
            $roomIds = array_merge($roomIds, $roomIdsCheckOut);
        }

        $rental->rooms()->sync($roomIds);
        $rental->records()->save($newRecord);
        $rental->moveDispatch();

        return response()->json($rental);
    } else {
        return response()->validation_error($rental->errors());
    }
  }*/

  
}