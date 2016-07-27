<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Setting extends Ardent {
  
  protected $fillable = [
    'price_day', 
    'price_hour', 
    'time_minimum',
    'active_impost',
    'impost'
  ];

  public function calculateImpost($amount) {
    $impost = 0;

     if($this->active_impost) {
        $impostPos = $this->impost / 100;
        $impost = $amount * $impostPos;
     }

     return $impost;
  }
}