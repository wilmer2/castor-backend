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

  public static $rules = [
    'price_day' => 'required|numeric',
    'price_hour' => 'required|numeric',
    'time_minimum' => 'required|date_format:H:i:s',
    'impost' => 'numeric'
  ];

  public static $customMessages = [
    'price_day.required' => 'El precio por dia es obligatori0',
    'price_day.numeric' => 'El precio por dia deber ser un número',
    'price_hour.required' => 'El precio por hora es obligatorio',
    'price_hour.numeric' => 'El precio por hora debe ser un número',
    'time_minimum.required' => 'El tiempo minimo es obligatorio',
    'time_minimum.date_format' => 'El tiempo minimo es un formato inválido',
    'impost.numeric' => 'El impuesto debe ser un número'
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