<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Record extends Ardent {
  protected $fillable = [
    'rental_id', 
    'move_id',
    'type', 
    'arrival_date', 
    'departure_date', 
    'departure_time' ,
    'first',
    'payment_type',
    'amount',
    'amount_total'
  ];

  public function rental() {
    return $this->belongsTo(Rental::class);
  }

  public function move() {
    return $this->belongsTo(Move::class);
  }

}