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
          if($rental->extra_hour != null) {
             $amountRental = $this->calculateAmountDayTimeExtra($rental, $setting);
          } else {
             $amountRental = $this->calculateAmountDay($rental, $setting, $rooms);
          }
          
      } else {
          $amountRental = $this->calculateAmountTime($rental, $setting, $rooms);
      }

      $amount = $this->calculateDiscount($amountRental, $rental->discount);
      $impost = $setting->calculateImpost($amount);
      $total = $amount + $impost;
      
      $rental->extra_hour = null;
      $rental->amount = $amount;
      $rental->amount_impost = $impost;
      $rental->amount_total = $total;

      $rental->forceSave();
    }

    public function calculateAmountDay($rental, $setting, $rooms) {
      if($rental->checkout_date != null) {
          $endDate = Carbon::parse($rental->checkout_date);
      } else {
          $endDate = Carbon::parse($rental->departure_date);
      }

      $amount = 0;  

      foreach ($rooms as $room) {
        if($room->pivot->check_timeout == null) {
            if($room->pivot->check_in != null) {
                $startDate = Carbon::parse($room->pivot->check_in);
            } else {
                $startDate = Carbon::parse($rental->arrival_date);
            }
             
            if($room->pivot->check_out != null) {
                $checkOut = Carbon::parse($room->pivot->check_out);

                $days = $startDate->diff($checkOut)->days;
            } else {
                $days = $startDate->diff($endDate)->days;
            }
          
            $amountExtra = $room->increment + $setting->price_day;
            $amountPerRoom = $amountExtra * $days;
        } else {

            if($room->pivot->check_timein != null) {
                $fromTime = strtotime($room->pivot->check_in.' '.$room->pivot->check_timein);
            } else {
                $fromTime = strtotime($rental->arrival_date.' '.$rental->arrival_time);
            }

            $toTime = strtotime($room->pivot->check_out.' '.$room->pivot->check_timeout);
            $increment = $room->increment;

            $amountPerRoom = $this->calculateAmountTimePerRoom($fromTime, $toTime, $setting, $increment);
        }  

        $amount += $amountPerRoom;
      }

      return $amount;
    }

    public function calculateAmountTime($rental, $setting, $rooms) {
      $amount = 0;

      foreach ($rooms as $room) {
        
        if($room->pivot->check_timeout != null) {
            $departureTime = $room->pivot->check_timeout;
        } else {
            $departureTime = $rental->departure_time;
        }

        if($rental->departure_date != null) {
            $toTime = strtotime($rental->departure_date.' '.$departureTime);
        } else {
            $toTime = strtotime($rental->arrival_date.' '.$departureTime);  
        }

        if($room->pivot->check_timein != null) {
            $fromTime = strtotime($room->pivot->check_in.' '.$room->pivot->check_timein);
        } else {
            $fromTime = strtotime($rental->arrival_date.' '.$rental->arrival_time);
        }

        $increment = $room->increment;
        $amountPerHour = $this->calculateAmountTimePerRoom($fromTime, $toTime, $setting, $increment);

        $amount += $amountPerHour;
      }

      return $amount;
    }

    public function calculateAmountTimePerRoom($fromTime, $toTime, $setting, $increment) {
      $totalTime = calculateTotalHours($fromTime, $toTime);
      
      $amountPerHour = $totalTime * $setting->price_hour;
      $amountIncrement = $amountPerHour + $increment;

      return $amountIncrement;
    }

    public function calculateAmountDayTimeExtra($rental, $setting) {
      $totalTime = explode(':', $rental->extra_hour);
      $extraHours = $totalTime[0];
      $amountPerHour = $extraHours * $setting->price_hour;

      $countRooms = $rental->getEnabledRooms()
      ->count();

      $amountTotalRooms = $countRooms * $amountPerHour;
      $amountAccumulative = $rental->amount + $amountTotalRooms; 

      return $amountAccumulative;
    }

    public function calculateDiscount($amount, $discount) {
      $result = $amount - $discount;

      if($result < 1) {
          $result = 0;
      }

      return $result;
    }

}
