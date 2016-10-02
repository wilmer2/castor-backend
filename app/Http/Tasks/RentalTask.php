<?php

namespace App\Http\Tasks;

use Carbon\Carbon;
use App\Validators\ValidationException;
use App\Models\Move;

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

    $amountPerRoom = $rooms->count() * $amount;

    $rental->amount = $this->calculateTotal($rental, $rooms, $amountPerRoom);

    $this->savePayment($rental);
    $this->assingMove($rental);
  }

  public function addRoomsDate($rental, $startDate, $roomIds) {
    if(!$rental->reservation && $rental->arrival_date < $startDate) {
        $currentHour = currentHour();
        $roomSyncIds = syncCheckinHour($roomIds, $startDate, $currentHour);
        $rental->syncRooms($roomSyncIds);
    } else {
        $rental->syncRooms($roomIds);
    }

    $rooms = $rental->whereInRooms($roomIds);

    $amountPerDay = $this->calculateAmountDay($startDate, $rental->departure_date);
    $amountPerRoom = $rooms->count() * $amountPerDay;
    $amount = $this->calculateTotal($rental, $rooms, $amountPerRoom);

    $rental->amount += $amount;
    
    $this->savePayment($rental);
  }

  public function addRoomsHour($rental, $roomIds) {
    if($rental->reservation) {
        $startTime = $rental->arrival_time;
        $rental->syncRooms($roomIds);
    } else {
        $date = currentDate();
        $startTime = currentHour();

        $roomsSyncTime = syncCheckinHour($roomIds, $date, $startTime);
        $rental->syncRooms($roomsSyncTime);
    }

    $rooms = $rental->whereInRooms($roomIds);

    $amountPerHour = $this->calculateAmountHour(
      $rental->arrival_date,
      $startTime,
      $rental->departure_time,
      $rental->departure_date
    );

    $amountPerRoom = $rooms->count() * $amountPerHour;
    $amount = $this->calculateTotal($rental, $rooms, $amountPerRoom);

    $rental->amount += $amount;

    $this->savePayment($rental);
  }

  public function renovateDate($rental, $renovateRoomIds, $oldType) {
    $roomsEnabled = $rental->getEnabledRoomsId();
    $roomsEnabled = $rental->getEnabledRoomsId();
    $oldRoomIds = array_diff($roomsEnabled, $renovateRoomIds);
    $newRoomIds = [];
    
    if(count($oldRoomIds) > 0) {
        if($oldType == 'hours') {
            $rental->syncRooms($renovateRoomIds, true);
        } else {
            $newRoomIds = array_diff($renovateRoomIds, $roomsEnabled);
            $newRoomsSync = syncData($newRoomIds, $rental->old_departure);
            $oldRoomsSync = syncDataCheckout($oldRoomIds, $rental->old_departure);

            $rental->syncRooms($newRoomsSync);
            $rental->syncRooms($oldRoomsSync);
        }
    } 

    if($oldType == 'hours')  {
        $rental->amount = 0;
        $newRoomIds = $renovateRoomIds;
    }

    $amountPerDay = $this->calculateAmountDay($rental->old_departure, $rental->departure_date);
    $amount = count($renovateRoomIds) * $amountPerDay;

    if(count($newRoomIds) > 0) {
        $rooms = $rental->whereInRooms($newRoomIds);
        $amount = $this->calculateTotal($rental, $rooms, $amount);
    }

    $rental->amount += $amount;

    $this->savePayment($rental);
  }

  public function renovateHour($rental, $renovateRoomIds, $oldDepartureTime) {
    $rental->extra_hour = null;

    if($rental->departure_date == null) {
        $startDate = $rental->arrival_date;
        $departureDate = $rental->arrival_date;
    } else {
        $startDate = $rental->departure_date;
        $departureDate = $rental->departure_date;
    }

    $roomsEnabled = $rental->getEnabledRoomsId();
    $oldRoomIds = array_diff($roomsEnabled, $renovateRoomIds);
    $newRoomIds = [];

    if(count($oldRoomIds) > 0) {
       $newRoomIds = array_diff($renovateRoomIds, $roomsEnabled);
       $newRoomsSyncTime = syncCheckinHour($newRoomIds, $departureDate, $oldDepartureTime);
       $oldRoomsSyncTime = syncCheckoutHour($oldRoomIds, $departureDate, $oldDepartureTime);

       $rental->syncRooms($newRoomsSyncTime);
       $rental->syncRooms($oldRoomsSyncTime);
    }

    $amountPerHour = $this->calculateAmountHour(
      $startDate,
      $oldDepartureTime,
      $rental->departure_time,
      $rental->departure_date
    );

    $amount = count($renovateRoomIds) * $amountPerHour;

    if(count($newRoomIds) > 0) {
        $rooms = $rental->whereInRooms($newRoomIds);
        $amount = $this->calculateTotal($rental, $rooms, $amount);
    }

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

  public function calculateTotal($renatal, $rooms, $amount) {
    foreach ($rooms as $room) {
      $amount += $room->increment;
      $room->pivot->price_base = $room->increment;
      $room->pivot->save();
    }

    return $amount;
  }


  public function savePayment($rental) {
    $impost = $this->setting->calculateImpost($rental->amount);
    $total = sumNum($rental->amount, $impost);

    $rental->amount_impost = $impost;
    $rental->amount_total = $total;

    $rental->forceSave();
  }

  public function assingMove($rental) {
    $user = currentUser();
    $move = $this->getMove($user->id, $rental->arrival_date);

    if($rental->move_id == null) {
        $rental->move_id = $move->id;
        $rental->forceSave();
    } else {
        $moveRental = Move::find($rental->move_id);

        if($moveRental->date != $rental->arrival_date) {
            $rental->move_id = $move->id;
            $rental->forceSave();

            $moveRental->checkRentals();            
        }
    }
  }

  public function removeRoom($rental, $roomId) {
    $countRoomAvailable = $rental->rooms()
    ->where('check_out', null)
    ->count();

    if($countRoomAvailable == 1) {
        $message = 'El hospedaje debe tener al menos una habitación';

        throw new ValidationException("Error Processing Request", $message);
    }

    $room = $rental->findRoom($roomId);
    
    if(!$room) {
        $message = 'Habitación no encontrada';

        throw new ValidationException("Error Processing Request", $message);
    }

    $restPayment = $this->getRestPayment($rental, $room);

    $room->state = 'disponible';
    $room->save();
    
    $rental->rooms()->detach($roomId);
    $rental->amount -= $restPayment;
    $this->savePayment($rental);
  }

  public function getRestPayment($rental, $room) {
    if($room->pivot->check_in != null) {
        $startDate = $room->pivot->check_in;
    } else {
        $startDate = $rental->arrival_date;
    }   

    if($rental->type == 'days') {
        $amountRest = $this->calculateAmountDay($startDate, $rental->departure_date);
    } else {
        if($room->pivot->check_timein != null) {
            $startTime = $room->pivot->check_timein;
        } else {
            $startTime = $rental->arrival_time;
        }

        $amountRest = $this->calculateAmountHour(
          $startDate,
          $startTime, 
          $rental->departure_time,
          $rental->departure_date
        );
    }

    $amountRest += $room->pivot->price_base;

    return $amountRest;
  }

  public function changeRoom($rental, $newRoom, $oldRoom, $state) {
    if($oldRoom->pivot->check_out != null) {
        $message = 'La habitación ya tiene salida';

        throw new ValidationException("Error Processing Request", $message);
    }

    $date = currentDate();
    $checkIn = $oldRoom->pivot->check_in;

    if(
        $rental->reservation || 
        $rental->arrival_date == $date || 
        $rental->type == 'hours' ||
        $checkIn != null &&
        $checkIn == $date
    ) {  
         $rental->amount -= $oldRoom->pivot->price_base;
         $rental->rooms()->detach($oldRoom->id);

         $sync = syncWithPrice($newRoom->id, $newRoom->type->increment, $rental->type, $checkIn);
    } else {
         $time = currentHour();
         $oldRoom->pivot->check_out = $date;
         $oldRoom->pivot->check_timeout = $time;
         $oldRoom->pivot->save();
        
         $sync = syncWithPrice($newRoom->id, $newRoom->type->increment, $rental->type, $date);
    }

    $rental->amount += $newRoom->type->increment;
    $oldRoom->state = $state;
    $oldRoom->save();

    $rental->syncRooms($sync);
    
    $this->savePayment($rental);
  }

  private function getMove($userId, $date) {
     $move =  Move::where('user_id', $userId)
     ->where('date', $date)
     ->first();

     if(!$move) {
        $move = new Move();
        $move->date = $date;
        $move->user_id = $userId;

        $move->save();
     }

     return $move;
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

  public function validRenovate($rental) {
    $this->validCheck($rental);
    $date = currentDate();

     if($rental->reservation) {
         $message = 'La reservación debe ser confirmada';
        
         throw new ValidationException("Error Processing Request", $message);
     }

     if($rental->type == 'days' && $rental->departure_date  > $date) {
        $message = 'Solo puede renovar en la fecha de salida';

        throw new ValidationException("Error Processing Request", $message);
     }
  }

}