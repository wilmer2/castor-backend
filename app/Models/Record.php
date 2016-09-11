<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Record extends Ardent {
  protected $fillable = [
    'rental_id', 
    'vehicle_type',
    'vehicle_description',
    'blanket'
  ];

  
  public static $rules = [
    'vehicle_description' => 'required_with:vehicle_type',
    'vehicle_type' => 'in:camioneta,moto,carro'
  ];


  public static $customMessages = [
    'vehicle_description.required_with' => 'La descripción es obligatoria para tipo de vehiculo',
    'vehicle_type.in' => 'El tipo de vehiculo no es valido'
  ];

  public function rental() {
    return $this->belongsTo(Rental::class);
  }

}