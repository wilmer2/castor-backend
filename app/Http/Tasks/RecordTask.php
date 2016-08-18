<?php

namespace App\Http\Tasks;
use Carbon\Carbon;

class RecordTask {
  private $setting;

  public function __construct() {
    $this->setting = getSetting();
  }

  public function checkoutRoomDate($rental, $room) {
    $rental->checkout_room = true;
    $records = $rental->records()
    ->orderBy('created_at', 'asc')
    ->get();

    $checkoutDate = $room->pivot->check_out;

    $recordFind = $records->first(function ($key, $record) use ($checkoutDate) {
       return $record->arrival_date <= $checkoutDate && $record->departure_date >= $checkoutDate;
    });
     
    $diffDays = diffDays($checkoutDate, $recordFind->departure_date);
    $amount = $diffDays * $this->setting->price_day;
    $amountDiff = sumNum($amount, $room->increment);

    $changeRecords = $records->filter(function ($value, $key) use ($checkoutDate) {
       return $value->departure_date >= $checkoutDate;
    });

    foreach($changeRecords as $changeRecord) {
      $changeRecord->amount -= $amountDiff;
      $impostRecord = $this->setting->calculateImpost($changeRecord->amount);

      $changeRecord->amount_total = $changeRecord->amount + $impostRecord;
      
      $changeRecord->save();
    } 

  }
}

