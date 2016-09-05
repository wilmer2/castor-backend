<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Client;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Record;
use App\Http\Tasks\RoomTask;
use App\Http\Tasks\RentalTask;
use App\Validators\RentalValidator;
use App\Validators\ValidationException;

class RentalController extends Controller {

  public function index() {
    $rentals = Rental::where('checkout', 0)
    ->where('reservation', 0)
    ->get();

    foreach ($rentals as $rental) {
      $rental->isCheckout();
    }

    $filterRentals = $rentals->filter(function ($rental, $key) {
      return $rental->checkout == 0;
    });

    return response()->json($filterRentals);
  }

  public function store(Request $request, RentalTask $rentalTask) {
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
        $rentalTask->registerPayment($newRental, $inputData['room_ids']);
        $newRental->moveDispatch();   

        return response()->json($newRental);
    } else {
        return response()->validation_error($newRental->errors());
    }
  }


  public function show(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $rental = $rental->getData();

    return response()->json($rental);
  }

  public function getRentalEnabledRooms(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $enabledRooms = $rental->getEnabledRooms()
    ->selectRooms()
    ->get();
    
    return response()->json([
        'rental' => $rental,
        'available_rooms' => $enabledRooms
    ]);
  }


  public function changeRoom(Request $request, RentalTask $rentalTask, $rentalId, $roomId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();

    try {
          $rentalTask->validCheck($rental);

          $room = $rental->findRoom($roomId);

          if(!$room) {
            return response()->validation_error('Habitación no encontrada');
          }

          $newRoom = Room::find($request->get('room_id'));
          $state = $request->get('state');

          $rentalTask->changeRoom($rental, $newRoom, $room, $state);

          $rental->moveDispatch();

          return response()->json($rental);

    } catch (ValidationException $e) {
          return response()->validation_error($e->getErrors());
    }
  }


  public function addRoomsDate(Request $request, RentalValidator $rentalValidator, RentalTask $rentalTask, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $date = currentDate();
    $hour = currentHour();

    try {
           $rentalTask->validDate($rental);
           $roomIds = $request->get('room_ids');

           if($rental->reservation && $rental->arrival_date >= $date) {
              $date = $rental->arrival_date;
              $hour = $rental->arrival_time;
           }

           $validRooms = $rentalValidator->isValidRoomDate($roomIds, $date, $rental->departure_date, $hour);

           if(!$validRooms) {
              return response()->validation_error('Alguna de las habitaciones no esta disponible');
           }

           $rentalTask->addRoomsDate($rental, $date, $roomIds);
           $rental->moveDispatch();

           return response()->json($rental);
    } catch (ValidationException $e) {
           return response()->validation_error($e->getErrors());
    }

   
  }


  public function addRoomsHour(Request $request, RentalValidator $rentalValidator, RentalTask $rentalTask , $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    try {

          $rentalTask->validHour($rental);

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

          $rentalTask->addRoomsHour($rental, $roomIds);
          $rental->moveDispatch();

          return response()->json($rental);

    } catch (ValidationException $e) {
          return response()->validation_error($e->getErrors());
    }
  }


  public function renovateHour(Request $request, RentalTask $rentalTask, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    try {

          $rentalTask->validCheck($rental);

          if($rental->type == 'days') {
              return response()->validation_error('El hospedaje debe ser por horas');
          }

          $inputData = $request->only('renovate_hour', 'room_ids');
          $oldDepartureTime = $rental->departure_time;

          if($rental->update($inputData)) {
             $rentalTask->renovateHour(
               $rental, 
               $inputData['room_ids'], 
               $oldDepartureTime
           );

           $rental->moveDispatch();

           return response()->json($rental);
        } else {
           return response()->validation_error($rental->errors());
        }
    } catch (ValidationException $e) {
         return response()->validation_error($e->getErrors());
    }
  }


  public function renovateDate(Request $request, RentalTask $rentalTask ,$rentalId) {
    $rental = Rental::findOrFail($rentalId);

    try {
        
          $rentalTask->validRenovate($rental);
    
          $oldType = $rental->type;

          $rental->setOldDeparture($oldType);           
          $rental->type = 'days';

          $rental->departure_time = createHour('12:00:00');

          $inputData = $request->only('departure_date', 'room_ids');

          if($rental->update($inputData)) {
             $rentalTask->renovateDate($rental, $inputData['room_ids'], $oldType);
             $rental->moveDispatch();

             return response()->json($rental);
          } else {
             return response()->validation_error($rental->errors());
          }

    } catch (ValidationException $e) {
          return response()->validation_error($e->getErrors());
    }
  }


  public function checkout(Request $request, RentalTask $rentalTask, $rentalId) {
    $rental = Rental::findOrFail($rentalId);

    try {
          $rentalTask->validCheck($rental);

          if($rental->reservation) {
             return response()->validation_error('Debe confirmar reservación');
          }

          if($rental->type == 'days') {
              $date = currentDate();
              $rental->checkout_date = $date;
          }

          $rental->checkout = 1;
          $rental->forceSave();
          $rental->moveDispatch();

          return response()->json(['message' => 'Salida confirmada']);
    } catch (ValidationException $e) {
          return response()->validation_error($e->getErrors());
    }
  }

  public function removeRoom(Request $request, RentalTask $rentalTask , $rentalId, $roomId) {
    $rental = Rental::findOrFail($rentalId);
    
    try {
          $rentalTask->validCheck($rental);
          $rentalTask->removeRoom($rental, $roomId);

          $rental->moveDispatch();

          return response()->json(['message' => 'Habitación a sido removida']);

    } catch (ValidationException $e) {
          return response()->validation_error($e->getErrors());
    }

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