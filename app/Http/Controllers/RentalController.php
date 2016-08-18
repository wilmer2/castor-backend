<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Client;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Record;
use App\Http\Tasks\RoomTask;
use App\Http\Tasks\RecordTask;
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
        //$newRental->registerRecord();
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

    if(
        $room->pivot->check_out != null && 
        $room->pivot->check_out <= $date &&
        $rental->type == 'days' ||
        $rental->type == 'hours' &&
        $room->pivot->check_out != null
    ) {
        return response()->validation_error('La habitación ya tiene salida');
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

    $room->state = $request->get('state');
    $room->save();

    $rental->syncRooms($sync);
    $rental->moveDispatch();

    return response()->json(['message' => 'Habitación a sido cambiada']);
  }


  public function addRoomsDate(Request $request, RentalValidator $rentalValidator, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();
    $hour = currentHour();

    if($rental->isCheckout()) {
        return response()->validation_error('Este hospedaje no puede agregar habitaciones');
    }

    if($rental->type == 'hours') {
        return response()->validation_error('El hospedaje debe ser por días');
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

    $rental->syncRooms($sync);
    $rental->moveDispatch();

    return response()->json(['message' => 'Habitaciones registradas']);
  }


  public function addRoomsHour(Request $request, RentalValidator $rentalValidator, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    if($rental->type == 'days') {
        return response()->validation_error('El hospedaje debe ser por horas');
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

    $rental->syncRooms($roomIds);
    $rental->moveDispatch();

    return response()->json(['message' => 'Habitaciones registradas']);
  }


  public function checkoutRoomDate(
    Request $request,
    RecordTask $recordTask, 
    $rentalId, 
    $roomId
  ) {

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
      $room->state = 'mantenimiento';
      $room->save();

      //$recordTask->checkoutRoomDate($rental, $room);

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
    $room->state = 'mantenimiento';
    $room->save();

    $rental->confirmCheckoutRoom();

    return response()->json(['message' => 'Salida de habitación confirmada']);
  }


  public function renovateHour(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    if($rental->reservation) {
        return response()->validation_error('La reservación debe ser confirmada');
    }

    $inputData = $request->only('renovate_hour', 'room_ids', 'discount');
    $departureTime = $rental->departure_time;
    $departureDate = $rental->departure_date;
    //$paymenType = $request->get('payment_type');

    if($rental->update($inputData)) {
        //$newRecord = new Record();
        
        $rental->checkRoomsRenovateHour(
            $inputData['room_ids'],  
            $departureTime, 
            $departureDate
        );

        //$rental->setRecord($newRecord, $paymenType);
        $rental->moveDispatch();

        return response()->json(['message' => 'Hospedaje ha sido renovado']);
    } else {
        return response()->validation_error($rental->errors());
    }
  }


  public function renovateDate(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();

    if($rental->isCheckout()) {
        return response()->validation_error('El hospedaje ya tiene salida');
    }

    if($rental->reservation) {
        return response()->validation_error('La reservación debe ser confirmada');
    }

    /*$recordHaving = $rental->records()
    ->where('type', 'days')
    ->where('departure_date', $request->get('departure_date'));*/

    /*if($recordHaving->count() > 0) {
        return response()->validation_error('Ya tiene renovación  para esta fecha');
    }*/
                   
    $rental->type = 'days';
    $rental->departure_time = createHour('12:00:00');
    $rental->setOldDeparture();

    $inputData = $request->only('departure_date', 'room_ids');
    $staticRoomIds = $request->get('static_rooms');
    //$paymenType = $request->get('payment_type');

    if($rental->update($inputData)) {
        ///$newRecord = new Record();

        $rental->checkRoomsRenovateDate($inputData['room_ids'], $staticRoomIds);
        //$rental->setRecord($newRecord, $paymenType);
        $rental->moveDispatch();

        return response()->json(['message' => 'Hospedaje ha sido renovado']);
    } else {
        return response()->validation_error($rental->errors());
    }
  }


  public function checkout(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();

    if($rental->reservation) {
        return response()->validation_error('Debe confirmar reservación');
    }

    if($rental->arrival_date == $date && $rental->type  == 'days') {
        return response()->validation_error('El hospedaje debe tener por los menos un dia para salida');
    }

    if($rental->type != 'hours') {
        $rental->deleteCheckRoomsDate($date);
        $rental->changeCheckoutDate($date);
    }

    if($date < $rental->departure_date && $rental->type == 'days') {
        $rental->checkout_date = $date;
    }
     
    $rental->checkout = 1;
    $rental->forceSave();
    $rental->moveDispatch();

    return response()->json(['message' => 'Salida confirmada']);
  }


  public function cancel(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->checkout) {
        return response()->validation_error('Hospedaje ya tiene salida');
    }

    $rental->cancelRooms();
    $rental->state = 'cancelado';
    $rental->checkout = 1;
    $rental->forceSave();
    //$rental->records()->forceDelete();

    return response()->json(['message' => 'Hospedaje cancelado']);
  }
  

  public function delete(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    if($rental->state != 'cancelado') {
        return response()->validation_error('El hospedaje no puede ser borrado');
    }

    $rental->delete();

    return response(['message' => 'El hospedaje ha sido borrado']);
  }

  
}