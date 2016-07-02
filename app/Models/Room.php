<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Room extends Ardent {
  
   protected $fillable = ['code_number', 'state', 'type_id'];

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
     return $this->belongsTo(Type::class);
   }
}