<?php

namespace App\Http\Tasks;

use Carbon\Carbon;

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

    $rooms = $rental->rooms()
    ->selectRooms()
    ->whereIn('rooms.id', $roomIds)
    ->get();

    $amountPerDay = $this->calculateAmountDay($startDate, $rental->departure_date);
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
     var_dump($rental->amount);
    
    $impost = $this->setting->calculateImpost($rental->amount);
    $total = sumNum($rental->amount, $impost);

    $rental->amount_impost = $impost;
    $rental->amount_total = $total;

    $rental->forceSave();
  }

}