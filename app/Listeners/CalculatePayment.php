<?php

namespace App\Listeners;

use App\Events\RentalWasAssigned;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;

class CalculatePayment
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  RentalWasAssigned  $event
     * @return void
     */
    public function handle(RentalWasAssigned $event) {
      $rental = $event->rental;
      $setting = getSetting();
      $rooms = $rental->rooms()
      ->selectRooms()
      ->get();

      if($rental->type == 'days') {
          $amount = $this->calculateAmountDay($rental, $setting, $rooms);
      } else {
          $amount = $this->calculateAmountTime($rental, $setting, $rooms);
      }

      $impost = $setting->calculateImpost($amount);
      $total = $amount + $impost;

      $rental->amount = $amount;
      $rental->amount_impost = $impost;
      $rental->amount_total = $total;

      $rental->forceSave();
      
    }

    public function calculateAmountDay($rental, $setting, $rooms) {
      $startDate = Carbon::parse($rental->arrival_date);

      if($rental->checkout_date != null) {
          $endDate = Carbon::parse($rental->checkout_date);
      } else {
          $endDate = Carbon::parse($rental->departure_date);
      }

      $amount = 0;

      foreach ($rooms as $room) {
          if($room->check_out != null) {
              $days = $startDate->diff($room->check_out)->days;
          } else {
              $days = $startDate->diff($endDate)->days;
          }
          
          $amountExtra = $room->increment + $setting->price_day;
          $amountPerRoom = $amountExtra * $days;

          $amount += $amountPerRoom;
      }

      return $amount;
    }

    public function calculateAmountTime($rental, $setting, $rooms) {
      if($rental->departure_date != null) {
          $toTime = strtotime($rental->departure_date.' '.$rental->departure_time);
      } else {
          $toTime = strtotime($rental->arrival_date.' '.$rental->departure_time) ;          
      }

      $fromTime = strtotime($rental->arrival_date.' '.$rental->arrival_time);

      $time = round(abs($fromTime - $toTime) / 60,2);
      $totalTime = ceil($time * (1/60));

      $amountPerHour = $totalTime * $setting->price_hour;
      $amount = $rooms->count() * $amountPerHour;
      
      foreach ($rooms as $room) {
        $amount += $room->increment;
      }

      return $amount;
    }

}
