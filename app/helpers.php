<?php

use App\Models\Setting;
use Carbon\Carbon;

function createHour($hour) {
  return date("H:i:s", strtotime($hour));
}

function sumHour($hour, $sumHour) {
   $time = explode(':', $sumHour);
   $addHour = date('H:i', strtotime($hour. '+ '. $time[0].' hours'));
   $totalTime = date('H:i', strtotime($addHour. '+'.$time[1].' minutes'));

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


function currentUser() {
  return auth()->user();
}
