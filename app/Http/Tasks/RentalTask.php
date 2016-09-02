<?php

namespace App\Http\Tasks;

use Carbon\Carbon;
use App\Validators\ValidationException;

class RentalTask {
  private $setting;
  
  public function __construct() {
    $this->setting = getSetting();
  }

  public function registerPayment($rental, $roomIds) {
    $rental->syncRooms($roomIds, true);
    $rooms = $rental->getRooms();

    if($rental->type == 'days') {
        $amount = $this->calculateAmountDay($rental->arrival_date, $rental->departure_date);
    } else {
        $amount = $this->calculateAmountHour(
          $rental->arrival_date, 
          $rental->arrival_time, 
          $rental->departure_time,
          $rental->departure_date
        );
    }

    $rental->amount = $this->calculateTotal($rooms, $amount);

    $this->savePayment($rental);
  }

  public function addRoomsDate($rental, $startDate, $roomIds) {
    if(!$rental->reservation && $rental->arrival_date < $startDate) {
        $roomSyncIds = syncData($roomIds, $startDate);
        $rental->syncRooms($roomSyncIds);
    } else {
        $rental->syncRooms($roomIds);
    }

    $rooms = $rental->whereInRooms($roomIds);

    $amountPerDay = $this->calculateAmountDay($startDate, $rental->departure_date);
    $amount = $this->calculateTotal($rooms, $amountPerDay);

    $rental->amount += $amount;
    
    $this->savePayment($rental);
  }

  public function addRoomsHour($rental, $roomIds) {
    $rental->syncRooms($roomIds);

    if($rental->reservation) {
        $startTime = $rental->arrival_time;
    } else {
        $startTime = currentHour();
    }

    $rooms = $rental->whereInRooms($roomIds);

    $amountPerHour = $this->calculateAmountHour(
      $rental->arrival_date,
      $startTime,
      $rental->departure_time,
      $rental->departure_date
    );

    $amount = $this->calculateTotal($rooms, $amountPerHour);

    $rental->amount += $amount;

    $this->savePayment($rental);
  }

  public function renovateDate($rental, $renovateRoomIds, $oldType) {
    $roomsEnabled = $rental->getEnabledRoomsId();
    $oldRoomIds = array_diff($roomsEnabled, $renovateRoomIds);

    if(count($oldRoomIds) > 0) {
        $newRoomIds = array_diff($renovateRoomIds, $roomsEnabled);
        $newRoomsSync = syncData($newRoomIds, $rental->old_departure);
        $oldRoomsSync = syncDataCheckout($oldRoomIds, $rental->old_departure);

        if($oldType == 'hours') {
            $rental->syncRooms($renovateRoomIds, true);
        } else {
            $rental->syncRooms($newRoomsSync);
            $rental->syncRooms($oldRoomsSync);
        }
    } 

    if($oldType == 'hours')  {
        $rental->amount = 0;
    }

    $rooms = $rental->whereInRooms($renovateRoomIds);
    $amountPerDay = $this->calculateAmountDay($rental->old_departure, $rental->departure_date);
    $amount = $this->calculateTotal($rooms, $amountPerDay);

    $rental->amount += $amount;

    $this->savePayment($rental);
  }

  public function calculateAmountDay($startDate, $endDate) {
    $startDate = Carbon::parse($startDate);
    $endDate = Carbon::parse($endDate);

    $days = $startDate->diff($endDate)->days;
    $amount = $days * $this->setting->price_day;

    return $amount;
  }

  public function calculateAmountHour($startDate, $startTime, $endTime, $endDate) {
    if($endDate != null) {
        $toTime = strtotime($endDate.' '.$endTime);
        $currentDate = currentDate();

        if($currentDate == $endDate) {
            $startDate = $currentDate;
        }

    } else {
        $toTime = strtotime($startDate.' '.$endTime);  
    }

    $fromTime = strtotime($startDate.' '.$startTime);
    $totalHours = calculateTotalHours($fromTime, $toTime);
    $amount = $totalHours * $this->setting->price_hour;
    
    return $amount;
  }

  public function calculateTotal($rooms, $amount) {
    $total = $rooms->count() * $amount;

    foreach ($rooms as $room) {
      $total += $room->increment;
    }

    return $total;
  }


  public function savePayment($rental) {
    $impost = $this->setting->calculateImpost($rental->amount);
    $total = sumNum($rental->amount, $impost);

    $rental->amount_impost = $impost;
    $rental->amount_total = $total;

    $rental->forceSave();
  }

  public function validCheck($rental) {
    if($rental->isCheckout()) {
        $message = 'Este hospedaje ya tiene salida';

        throw new ValidationException("Error Processing Request", $message);
    }

    if($rental->reservationExpired()) {
        $message = 'La reservación ya expiro';

        throw new ValidationException("Error Processing Request", $message);
    }
  }

  public function validDate($rental) {
    $date = currentDate();

    $this->validCheck($rental);

    if($rental->type == 'hours') {
        $message = 'El hospedaje debe ser por días';

        throw new ValidationException("Error Processing Request", $message);
    }

    if($rental->departure_date == $date) {
        $message = 'No puede agregar habitaciones en la fecha de salida';

        throw new ValidationException("Error Processing Request", $message);
    }

  }

  public function validHour($rental) {
    $this->validCheck($rental);
    
    if($rental->type == 'days') {
        $message = 'El hospedaje debe ser por horas';

        throw new ValidationException("Error Processing Request", $message);
    }

    if($rental->isTimeout()) {
        $message = 'Hora de hospedaje ya termino';
        
        throw new ValidationException("Error Processing Request", $message);
    }

  }

}