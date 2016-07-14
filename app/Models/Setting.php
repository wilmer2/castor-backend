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
}