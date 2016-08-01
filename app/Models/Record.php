<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Record extends Ardent {
  protected $fillable = ['rental_id', 'type', 'departure_date', 'departure_time' ,'first'];

  public function rental() {
    return $this->belongsTo(Rental::class);
  }

  /*public function setData($rental) {
    $this->type = $rental->type;
    $this->departure_date = $rental->departure_date;
    $this->departure_time = $rental->departure_time;
  }*/
}