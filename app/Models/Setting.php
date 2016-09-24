<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Setting extends Ardent {
  
  protected $fillable = [
    'name',
    'rif',
    'price_day', 
    'price_hour', 
    'time_minimum',
    'active_impost',
    'impost'
  ];

  public static $rules = [
    'name' => 'required',
    'rif' => 'required|between:10,10',
    'price_day' => 'required|numeric',
    'price_hour' => 'required|numeric',
    'time_minimum' => 'required|date_format:H:i:s',
    'impost' => 'required|numeric'
  ];

  public static $customMessages = [
    'name.required' => 'El nombre de empresa es obligatorio',
    'rif.required' => 'El rif es obligatorio',
    'rif.between' => 'El rif debe tener 10 caracteres',
    'price_day.required' => 'El precio por dia es obligatorio',
    'price_day.numeric' => 'El precio por dia deber ser un número',
    'price_hour.required' => 'El precio por hora es obligatorio',
    'price_hour.numeric' => 'El precio por hora debe ser un número',
    'time_minimum.required' => 'El tiempo minimo es obligatorio',
    'time_minimum.date_format' => 'El tiempo minimo es un formato inválido',
    'impost.required' => 'El inpuesto es obligatorio',
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