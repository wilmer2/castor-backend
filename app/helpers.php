<?php

use App\Models\Setting;
use Carbon\Carbon;

function createHour($hour) {
  return date("H:i:s", strtotime($hour));
}

function sumHour($hour, $sumHour) {
   $time = explode(':', $sumHour);
   $addHour = date('H:i:s', strtotime($hour. '+ '. $time[0].' hours'));
   $totalTime = date('H:i:s', strtotime($addHour. '+'.$time[1].' minutes'));

   return $totalTime;
}

function currentHour() {
  return date('H:i:s');
}

function currentDate() {
  return Carbon::today()->format('Y-m-d');
}

function addDay($date) {
  return Carbon::parse($date)->addDay()->format('Y-m-d');
}

function subDay($date) {
  return Carbon::parse($date)->subDay()->format('Y-m-d');
}

function subDays($date, $amount) {
  return Carbon::parse($date)->subDays($amount)->format('Y-m-d');
}

function getSetting() {
  return Setting::find(1);
}

function syncData($roomIds, $date) {
  $pivotData = array_fill(0, count($roomIds), ['check_in' => $date]);
  $syncData = array_combine($roomIds, $pivotData);

  return $syncData;
}

function syncDataCheckout($roomIds, $date) {
  $pivotData = array_fill(0, count($roomIds), ['check_out' => $date]);
  $syncDataCheckout = array_combine($roomIds, $pivotData);

  return $syncDataCheckout;
}

function syncCheckinHour($roomIds, $date, $time) {
  $pivotData = array_fill(0, count($roomIds), ['check_timein' => $time, 'check_in' => $date]);
  $syncDataCheckTimein = array_combine($roomIds, $pivotData);

  return $syncDataCheckTimein;
}

function syncCheckoutHour($roomIds, $date, $time) {
  $pivotData = array_fill(0, count($roomIds), ['check_timeout' => $time, 'check_out' => $date]);
  $syncDataCheckTimeout = array_combine($roomIds, $pivotData);

  return $syncDataCheckTimeout;
}

function syncWithPrice($roomId, $price, $type, $checkIn = null) {
  if($checkIn == null) {
      $sync = [$roomId => ['price_base' => $price]];
  } else {
      if($type == 'hours') {
          $time = currentHour();

          $sync = [$roomId => [
            'check_in' => $checkIn, 
            'price_base' => $price, 
            'check_timein' => $time
          ]];
      } else {
          $sync = [$roomId => ['check_in' => $checkIn, 'price_base' => $price]];

      }

  }

  return $sync;
}

function calculateTotalHours($fromTime, $toTime) {
  $time = round(abs($fromTime - $toTime) / 60,2);
  $totalTime = ceil($time * (1/60));

  return $totalTime;
}

function resNum($first, $second) {
  if($first > $second) {
      $total = $first - $second;
  } else {
      $total = $second - $first;
  }

  return $total;
}

function sumNum($first, $second) {
  $total = $first + $second;

  return $total;
}

function diffDays($fromDate, $toDate) {
  $fromDate = new Carbon($fromDate);
  $toDate = new Carbon($toDate);

  $diff = $fromDate->diff($toDate)->days;

  return $diff;
}

function currentUser() {
  return auth()->user();
}
