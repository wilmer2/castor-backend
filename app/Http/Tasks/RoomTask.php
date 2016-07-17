<?php

namespace App\Http\Tasks;

use App\Models\Room;
use App\Validators\RentalValidator;

class RoomTask {
  
  //container class
  private $rentalValidator;

  //common properties
  private $message;
  private $arrival_date;
  private $arrival_time;
  private $departure_time;
  private $departure_date;

  public function __construct(RentalValidator $rentalValidator) {
    $this->rentalValidator = $rentalValidator;
  }

  public function getRoomDateReservation($rentalId, $roomsId) {
    $arrivalDate = $this->arrival_date;
    $departureDate = $this->departure_date;
    $arrivalTime = $this->arrival_time;

    $rooms = Room::availableDatesRooms(
       $arrivalDate,
       $departureDate,
       $roomsId,
       $arrivalTime,
       $rentalId
    );

    $rooms = $this->getAvailableRoomReservation($rooms, $roomsId);

    if(!$rooms) {
        $rooms = $this->getAvailableDateRoom($rentalId);

        $rooms = [
          'rooms' => $rooms,
          'select' => true
        ];

        return $rooms;

    } else {
        return $rooms;
    }

  }

  public function getAvailableDateRoom($rentalId = null) {
    $arrivalDate = $this->arrival_date;
    $departureDate = $this->departure_date;
    $arrivalTime = $this->arrival_time;

    $rooms = Room::dateRooms(
       $arrivalDate, 
       $departureDate, 
       $arrivalTime, 
       $rentalId
    )
    ->get();

    return $rooms;
  }

  public function getRoomHourReservation($rentalId ,$roomsId) {
    $this->setDepartureTime();

    $arrivalDate = $this->arrival_date;
    $arrivalTime = $this->arrival_time;
    $departureTime = $this->departure_time;

    if($this->departure_date != null) {
        $departureDate = $this->departure_date;

        $rooms = Room::availableHourIntervalRoom(
           $arrivalDate,
           $departureDate,
           $arrivalTime,
           $departureTime,
           $roomsId,
           $rentalId
        );

    } else {
        $rooms = Room::availableHourRooms(
           $arrivalDate,
           $arrivalTime,
           $departureTime,
           $roomsId,
           $rentalId
        );
    }

    $rooms = $this->getAvailableRoomReservation($rooms, $roomsId);

    if(!$rooms) {
        $rooms = $this->getAvailableHourRoom($rentalId);

        $rooms = [
          'rooms' => $rooms,
          'select' => true
        ];

        return $rooms;
    }

    return $rooms;
  }

  public function getAvailableHourRoom($rentalId = null) {
    $this->setDepartureTime();

    $arrivalDate = $this->arrival_date;
    $arrivalTime = $this->arrival_time;
    $departureTime = $this->departure_time;

    if($this->departure_date == null) {
        $rooms = Room::hourRooms( 
           $arrivalDate, 
           $arrivalTime,
           $departureTime,
           $rentalId
        )
        ->get();
    } else {
        $departureDate = $this->departure_date;
        
        $rooms = Room::hourRoomInterval(
           $arrivalDate,
           $departureDate,
           $arrivalTime,
           $departureTime,
           $rentalId
        )
        ->get();
    }

    return $rooms;
        
  }

  public function getAvailableRoomReservation($rooms, $roomsId) {
    if($rooms->count() == count($roomsId)) {
        $rooms = $rooms->get();
        return $rooms;

    } else {
        return false;
    }
  }

  public function isValidDataQuery() {
    $valid = true;
    $arrivalDate = $this->arrival_date;
    $arrivalTime = $this->arrival_time;
    $departureDate = $this->departure_date;
    
    if(!$this->rentalValidator->isValidDate($arrivalDate)) {
        $valid = false;
        $this->setMessage('La fecha ya paso');
    }

    if(!$this->rentalValidator->isValidTime($arrivalDate, $arrivalTime)) {
        $valid = false;
        $this->setMessage('La hora ya paso');
    }

    if($departureDate != null) {
      if(!$this->rentalValidator->isValidBetweenDates($arrivalDate, $departureDate)) {
          $valid = false;
          $this->setMessage('Ingrese fechas correctamente');
      }
    } 
    
    return $valid;
  }

  public function setMessage($message) {
    $this->message = $message;
  }

  public function setData(
    $arrivalDate, 
    $arrivalTime, 
    $departureDate = null,
    $departureTime = null
  ) {

     $this->arrival_date = $arrivalDate;
     $this->arrival_time = $arrivalTime;
     $this->departure_date = $departureDate;
     $this->departure_time = $departureTime;
  }

  public function getMessage() {
    return $this->message;
  }

  public function setDepartureTime() {
    if($this->departure_time == '') {
        $setting = getSetting();

        $this->departure_time = sumHour($this->arrival_time, $setting->time_minimum);
    } 

    $this->setDepartureDateHour();
  }

  public function setDepartureDateHour() {
    if($this->arrival_time >= $this->departure_time) {
        $this->departure_date = addDay($this->arrival_date);
    }
  }
}