<?php

namespace App\Validators;

use App\Models\Room;

class RentalValidator {

  public function isValidDate($startDate) {
    $currentDate = currentDate();
    $beforeDate = subDay($currentDate);
    
    if($startDate <= $beforeDate) {
        return false;
    } else {
        return true;
    }
  }


  public function isValidBetweenDates($startDate , $departureDate) {
    if($departureDate <= $startDate) {
        return false;
    } else {
        return true;
    }
  } 

  public function isValidTime($startDate, $startHour) {
    $currentDate = currentDate();

    if($startDate == $currentDate) {
      $currentHour = currentHour();

      if($startHour < $currentHour) {
        return false;
      }
    }

    return true;
  }

  public function isValidRoomDate(
    $roomIds, 
    $arrivalDate, 
    $departureDate, 
    $hour, 
    $oldDepartureDate
  ) {

    if($oldDepartureDate != null) {
        $availableDateRoom = Room::availableDatesRooms($oldDepartureDate,$departureDate, $roomIds);
    } else {
        $availableDateRooms = Room::availableDatesRooms($arrivalDate, $departureDate, $roomIds, $hour);
    }

    if(count($roomIds) == $availableDateRooms->count()) {
        return true;
    } else {
        return false;
    }
  }

  public function isValidRoomHour(
    $roomIds,
    $timeFrom,
    $departureTime,
    $dateFrom,
    $departureDate
  ) {

     if($timeFrom > $departureTime) {
        $availableHourRooms = Room::availableHourIntervalRoom(
          $dateFrom, 
          $departureDate, 
          $timeFrom,
          $departureTime,
          $roomIds
        );
     } else {
        $availableHourRooms = Room::availableHourRooms(
          $dateFrom,
          $timeFrom,
          $departureTime,
          $roomIds
        );
      }

    if(count($roomIds) == $availableHourRooms->count()) {
        return true;
    } else {
        return false;
    }
  }
}