<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Client;
use App\Models\Rental;

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
        $newReservation->rooms()->attach($request->get('room_ids'));

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
        $rental->rooms()->sync($request->get('room_ids'));

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
        $rental->rooms()->sync($request->get('room_ids'));;
        
        $rental->moveDispatch();
        return response()->json($rental);
    } else {
        return response()->validation_error($rental->errors());
    }

  }
}
