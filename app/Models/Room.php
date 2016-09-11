<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Room extends Ardent {
  
   protected $fillable = ['code_number', 'state', 'type_id', 'available'];

   public static $rules = [
     'code_number' => 'required|unique:rooms,code_number',
     'type_id' => 'required|exists:types,id'
   ];

   public static $customMessages = [
     'code_number.required' => 'El codigo de habitación es obligatorio',
     'code_number.unique' => 'Ya existe habitación con este codigo',
     'type_id.required' => 'El tipo de habitación es obligatorio',
     'type_id.exists' => 'El tipo de habitación no existe'
   ];

   public function type() {
     return $this->belongsTo(Type::class, 'type_id');
   }

   public function rentals() {
     return $this->belongsToMany(Rental::class)
     ->withTimestamps()
     ->withPivot('check_out', 'check_in', 'check_timeout', 'check_timein', 'price_base');
   }

   /** Model Querys */

   public function scopeSelectRooms($query) {
     return $query->leftJoin('types','rooms.type_id', '=', 'types.id')
        ->select(
          'rooms.code_number',
          'rooms.state',
          'types.title',
          'types.description',
          'types.increment',
          'types.img_url',
          'types.id as typeId',
          'rooms.id as roomId'
        )
        ->where('state', '<>', 'desahabilitada');
   }

   public function scopeDateRooms(
     $query, 
     $arrivalDate, 
     $departureDate,  
     $arrivalHour = null,
     $rentalId = null
   ) {
     return $query->selectRooms()
        ->whereDoesntHave('rentals', function ($q) use (
           $arrivalDate, 
           $departureDate, 
           $arrivalHour,
           $rentalId
        ) {

          $q->where('arrival_date', '<', $departureDate)
              ->where('departure_date', '>=', $departureDate)
              ->where('checkout', 0)
              ->where('check_out', null);

              if($rentalId != null) {
                $q->where('id', '<>', $rentalId);
              }

              $q->orWhere(function ($q) use ($arrivalDate, $departureDate, $rentalId) {
                $q->where('arrival_date', '>=', $arrivalDate)
                  ->where('departure_date', '<=', $departureDate)
                  ->where('checkout', 0)
                  ->where('check_out', null);
                  
                if($rentalId != null) {
                    $q->where('id', '<>', $rentalId);
                }

              })
              ->orWhere(function($q) use ($departureDate, $rentalId) {
                 $q->where('arrival_date', $departureDate)
                    ->where('arrival_time', '<=', '12:00')
                    ->where('checkout', 0)
                    ->where('check_out', null);

                 if($rentalId != null) {
                    $q->where('id', '<>', $rentalId);
                 }
              })
              ->orWhere(function($q) use ($arrivalDate, $departureDate, $rentalId) {
                $q->where('arrival_date', '<=', $arrivalDate)
                  ->where('departure_date', '>', $arrivalDate)
                  ->where('departure_date', '<', $departureDate)
                  ->where('checkout', 0)
                  ->where('check_out', null);

                if($rentalId != null) {
                    $q->where('id', '<>', $rentalId);
                }

              })
              ->orWhere(function ($q) use ($arrivalDate, $departureDate, $rentalId) {
                  $q->where('arrival_date', '>', $arrivalDate)
                     ->where('arrival_date', '<', $departureDate)
                     ->where('type', 'hours')
                     ->where('checkout', 0)
                     ->where('check_out', null);                  

                  if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                  }
              });

              if($arrivalHour == null) {
                  $arrivalHour = '12:00';
              } 

              $q->orWhere(function($q) use ($arrivalDate, $arrivalHour, $rentalId) {
                $q->where('departure_date', $arrivalDate)
                  ->where('departure_time', '>=', $arrivalHour)
                  ->where('checkout', 0)
                  ->where('check_out', null);

                  if($rentalId != null) {
                    $q->where('id', '<>', $rentalId);
                  }

              })
              ->orWhere(function ($q) use ($arrivalDate, $arrivalHour, $rentalId) {  
                $q->where('arrival_date', $arrivalDate)
                  ->where('arrival_time', '>=', $arrivalHour)
                  ->where('type', 'hours')
                  ->where('checkout', 0)
                  ->where('check_out', null);

                  if($rentalId != null) {
                    $q->where('id', '<>', $rentalId);
                  }
                })
                ->orWhere(function ($q) use ($arrivalDate, $arrivalHour, $rentalId) {
                    $q->where('arrival_date', $arrivalDate)
                      ->where('departure_time', '>=', $arrivalHour)
                      ->where('type', 'hours')
                      ->where('checkout', 0)
                      ->where('check_out', null);

                  if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                  }
                })
                ->orWhere(function ($q) use ($departureDate, $rentalId) {
                    $q->where('arrival_date', $departureDate)
                      ->where('arrival_time', '<=', '12:00')
                      ->where('type', 'hours')
                      ->where('checkout', 0)
                      ->where('check_out', null);

                    if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                    }
                })
                ->orWhere(function ($q) use ($departureDate, $rentalId) {
                    $q->where('arrival_date', $departureDate)
                      ->where('departure_time', '<=', '12:00')
                      ->where('type', 'hours')
                      ->whereNull('departure_date')
                      ->where('checkout', 0)
                      ->where('check_out', null);

                    if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                    }
                });

        });
   }


   public function scopeAvailableDatesRooms(
    $query, 
    $arrivalDate, 
    $departureDate, 
    $roomsIds, 
    $arrivalHour = null,
    $rentalId = null
   ) { 
      return $query->dateRooms($arrivalDate, $departureDate, $arrivalHour, $rentalId)
          ->whereIn('rooms.id', $roomsIds);
   }

   public function scopeHourRooms(
     $query, 
     $arrivalDate, 
     $arrivalHour,
     $departureHour,
     $rentalId = null
   ) {
        return $query->selectRooms()
          ->whereDoesntHave('rentals', function ($q) use (
            $arrivalDate, 
            $arrivalHour, 
            $departureHour,
            $rentalId
        ) {

            $q->where('arrival_date', $arrivalDate)
              ->where('arrival_time', '<=', $departureHour)
              ->where('departure_time', '>=', $departureHour)
              ->where('type', 'hours')
              ->where('checkout', 0)
              ->where('check_out', null);

              if($rentalId != null) {
                  $q->where('id', '<>', $rentalId);
              }

              $q->orWhere(function ($q) use (
                 $arrivalDate, 
                 $arrivalHour, 
                 $departureHour, 
                 $rentalId
              ) {
                $q->where('arrival_date', $arrivalDate)
                  ->where('arrival_time', '>=', $arrivalHour)
                  ->where('departure_time', '<=', $departureHour)
                  ->whereNull('departure_date')
                  ->where('type', 'hours')
                  ->where('checkout', 0)
                  ->where('check_out', null);

                  if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                  }
              })
              ->orWhere(function ($q) use (
                  $arrivalDate, 
                  $arrivalHour, 
                  $departureHour, 
                  $rentalId
              ) {
                  $q->where('arrival_date', $arrivalDate)
                    ->whereBetween('arrival_time', array($arrivalHour, $departureHour))
                    ->where('type', 'hours')
                    ->where('checkout', 0)
                    ->where('check_out', null);

                  if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                  }

              })
              ->orWhere(function ($q) use (
                 $arrivalDate, 
                 $arrivalHour, 
                 $departureHour,
                 $rentalId
              ) {
                  $q->where('arrival_date', $arrivalDate)
                    ->where('arrival_time', '<=', $arrivalHour)
                    ->where('departure_time', '>=', $arrivalHour)
                    ->where('departure_time', '<=', $departureHour)
                    ->where('type', 'hours')
                    ->where('checkout', 0)
                    ->where('check_out', null);

                   if($rentalId != null) {
                       $q->where('id', '<>', $rentalId);
                   }
              })
              ->orWhere(function ($q) use ($arrivalDate, $rentalId) {
                 $q->where('arrival_date', '<', $arrivalDate)
                   ->where('departure_date', '>', $arrivalDate)
                   ->where('type', 'days')
                   ->where('checkout', 0)
                   ->where('check_out', null);

                 if($rentalId != null) {
                     $q->where('id', '<>', $rentalId);
                 }
              })
              ->orWhere(function ($q) use ($arrivalDate, $departureHour, $rentalId) {
                $q->where('arrival_date', $arrivalDate)
                  ->where('arrival_time', '<=', $departureHour)
                  ->where('type', 'days')
                  ->where('checkout', 0)
                  ->where('check_out', null);

                if($rentalId != null) {
                    $q->where('id', '<>', $rentalId);
                } 
              })
              ->orWhere(function ($q) use ($arrivalDate, $arrivalHour, $rentalId) {
                $q->where('departure_date', $arrivalDate)
                  ->where('departure_time', '>=', $arrivalHour)
                  ->where('checkout', 0)
                  ->where('check_out', null);

                if($rentalId != null) {
                    $q->where('id', '<>', $rentalId);
                }
              });
        });
   }

   public function scopeAvailableHourRooms(
     $query, 
     $arrivalDate, 
     $arrivalHour,
     $departureHour,
     $roomsIds,
     $rentalId = null
   ) {
      return $query->hourRooms($arrivalDate, $arrivalHour, $departureHour, $rentalId)
          ->whereIn('rooms.id', $roomsIds);
   }

   public function scopeHourRoomInterval(
     $query, 
     $arrivalDate, 
     $departureDate, 
     $arrivalHour, 
     $departureHour,
     $rentalId = null
   ) {  

      return $query->selectRooms()
          ->whereDoesntHave('rentals', function ($q) use (
             $arrivalDate, 
             $departureDate, 
             $arrivalHour,
             $departureHour,
             $rentalId
          ) {
             
            $q->where('arrival_date', $arrivalDate)
              ->where('arrival_time', '>=', $arrivalHour)
              ->where('type', 'hours')
              ->where('checkout', 0)
              ->where('check_out', null);

            if($rentalId != null) {
                $q->where('id', '<>', $rentalId);
            }

            $q->orWhere(function ($q) use ($arrivalDate, $arrivalHour, $rentalId) {
                  $q->where('arrival_date', $arrivalDate)
                    ->where('departure_time', '>', $arrivalHour)
                    ->where('type', 'hours')
                    ->where('checkout', 0)
                    ->where('check_out', null);

                  if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                  }
              })
              ->orWhere(function ($q) use ($departureDate , $departureHour, $rentalId) {
                  $q->where('arrival_date', $departureDate)
                    ->where('arrival_time', '<=', $departureHour)
                    ->where('type', 'hours')
                    ->where('checkout', 0)
                    ->where('check_out', null);

                  if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                  }
              })
              ->orWhere(function ($q) use ($departureDate, $rentalId) {
                  $q->where('departure_date', $departureDate)
                    ->where('type', 'hours')
                    ->where('checkout', 0)
                    ->where('check_out', null);

                  if($rentalId != null) {
                     $q->where('id', '<>', $rentalId);
                  }

              })
              ->orWhere(function ($q) use ($arrivalDate, $departureDate, $rentalId) {
                  $q->where('arrival_date', '<', $departureDate)
                    ->where('departure_date', '>=', $departureDate)
                    ->where('type', 'days')
                    ->where('checkout', 0)
                    ->where('check_out', null);

                  if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                  }
              })
              ->orWhere(function($q) use ($arrivalDate, $arrivalHour, $rentalId) {
                $q->where('departure_date', $arrivalDate)
                  ->where('departure_time', '>=', $arrivalHour)
                  ->where('checkout', 0)
                  ->where('check_out', null);

                if($rentalId != null) {
                      $q->where('id', '<>', $rentalId);
                }
                
              });
          });
    }

    public function scopeAvailableHourIntervalRoom(
      $query, 
      $arrivalDate, 
      $departureDate, 
      $arrivalHour, 
      $departureHour,
      $roomsIds,
      $rentalId = null
    ) { 

       return $query->hourRoomInterval(
         $arrivalDate,
         $departureDate, 
         $arrivalHour, 
         $departureHour,
         $rentalId
       ) 
       ->whereIn('rooms.id', $roomsIds);
    }

    public function hasRental() {
      return $this->rentals()
      ->where('checkout', 0);
    }


}