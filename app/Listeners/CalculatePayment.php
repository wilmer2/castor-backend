<?php

namespace App\Listeners;

use App\Events\RentalWasAssigned;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use App\Models\Record;

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
      $total = sumNum($amount, $impost);

      //$this->registerAmountRecord($rental, $amount, $setting); 
      
      $rental->extra_hour = null;
      $rental->amount = $amount;
      $rental->amount_impost = $impost;
      $rental->amount_total = $total;

      $rental->forceSave();
      //$this->hasCheckoutDate($rental, $setting);
    }

    /*public function registerAmountRecord($rental, $newAmout, $setting) {
     
      if($rental->checkout_date == null &&  !$rental->checkout_room) {
          $recordAmount =  resNum($newAmout, $rental->amount);
          $recordImpost = $setting->calculateImpost($recordAmount);
          $totalAmount = sumNum($recordAmount, $recordImpost);

          $record = $rental->lastRecord();
          $record->amount = $recordAmount;
          $record->amount_total = $totalAmount;

          $record->save();
      }
     
    }*/

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

    /*public function hasCheckoutDate($rental, $setting) {
      if($rental->checkout_date != null) {
          $records = $rental->records()
          ->orderBy('created_at', 'desc')
          ->get();

          $recordSameDate = $records->where('departure_date', $rental->checkout_date)
          ->first();

          $firstRecord = $records->where('first', 1)->first();

          $iterateRecords = $records->filter(function ($value, $key) {
              return $value->first == 0;
          });

          if($iterateRecords->count() > 0) {
             $this->setRecordCheckout(
               $rental, 
               $records, 
               $recordSameDate, 
               $iterateRecords, 
               $firstRecord, 
               $setting
             );
          } else {
              $this->editFirstRecord($rental, $firstRecord);
          }
      }
    }*/

    /*public function setRecordCheckout(
      $rental,
      $records, 
      $recordSameDate, 
      $iterateRecords, 
      $firstRecord, 
      $setting
    ) {
       $amount = $rental->amount;

       foreach($iterateRecords as $iterateRecord) {
          if($iterateRecord->departure_date > $rental->checkout_date) {
              $iterateRecord->delete();
          } else {
              if(!$recordSameDate) {
                $amount -= $iterateRecord->amount;
              }
          }
       }

       if($firstRecord->departure_date > $rental->checkout_date) {
          $this->editFirstRecord($rental, $firstRecord);
       } else {
          if(!$recordSameDate) {
              $firstRecord->conciliate = 0;
              $firstRecord->save();

              $lastRecord = $records->first(function ($key, $record) use ($rental) {
                  return $record->departure_date > $rental->checkout_date && $record->first == 0;
              });
          
              $impost = $setting->calculateImpost($amount);
              $total = sumNum($amount, $impost);

              $newRecord = new Record();
              $newRecord->amount = $amount;
              $newRecord->amount_total = $total;

              $rental->setRecord($newRecord, $lastRecord->payment_type);
         }
       }
    }*/

    /*public function editFirstRecord($rental, $firstRecord) {
      $firstRecord->amount = $rental->amount;
      $firstRecord->amount_total = $rental->amount_total;
      $firstRecord->departure_date = $rental->checkout_date;

      $firstRecord->save();
    }*/

}
