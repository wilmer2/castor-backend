<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Client;
use App\Models\Rental;
use App\Models\Room;
use App\Validators\RentalValidator;

class RentalController extends Controller {

  public function store(Request $request) {
    if($request->has('clientId')) {
        $clientId = $request->get('clientId');
        $client = Client::findOrFail($clientId);
    } else {
        $identityCard = $request->get('identity_card');
        $client = Client::searchForIdentityCard($identityCard);
    }

    $inputData = $request->all();
    $newRental = new Rental($inputData);

    $newRental->client_id = $client->id;
    $newRental->state = 'Conciliado';

    if($newRental->save()) {
        $newRental->rooms()->attach($request->get('room_ids'));

        return response()->json($newRental);
    } else {
        return response()->validation_error($newRental->errors());
    }
  }

  public function addReservation(Request $request) {
    if($request->has('clientId')) {
        $clientId = $request->get('clientId');
        $client = Client::findOrFail($clientId);
    } else {
        $identityCard = $request->get('identity_card');
        $client = Client::searchForIdentityCard($identityCard);
    }

    $inputData = $request->all();
    $newReservation = new Rental($inputData);

    if(isset($inputData['pay'])) {
        $newReservation->state = 'Conciliado';
    } else {
        $newReservation->state = 'Por pagar';
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

  public function getAvailableDateRoom(
    Request $request,
    RentalValidator $rentalValidator, 
    $rentalId
  ) {

     $rental = Rental::findOrFail($rentalId);
     $roomsId = $rental->getRoomsId();

     $arrivalDate = $request->get('arrival_date');
     $departureDate = $request->get('departure_date');
     $arrivalHour = $request->get('arrival_time');

     $validQueryData = $rentalValidator->isValidDataQuery(
        $arrivalDate, 
        $arrivalHour, 
        $departureDate
     );

     if(!$validQueryData) {
        return response()->validation_error($rentalValidator->getMessage());
     }

     $rooms = Room::availableDatesRooms(
        $arrivalDate,
        $departureDate,
        $roomsId,
        $arrivalHour,
        $rental->id
     );

     if(count($roomsId) == $rooms->count()) {
        $rooms = $rooms->get();

        return response()->json($rooms);
     } else {

        $rooms = Room::dateRooms(
           $arrivalDate, 
           $departureDate, 
           $arrivalHour, 
           $rental->id
        );

        $rooms = $rooms->get();

        return response()->json(['rooms' => $rooms, 'select' => true]);
     }
  }

  /*public function getAvalabelHourRommReservation(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
  }*/
}