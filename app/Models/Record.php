<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Record extends Ardent {
  protected $fillable = ['rental_id', 'type', 'departure_date', 'departure_time' ,'first'];

  public function rental() {
    return $this->belongsTo(Rental::class);
  }

}