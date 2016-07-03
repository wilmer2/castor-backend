<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Client extends Ardent {
  
  protected $fillable = ['identity_card', 'first_name', 'last_name', 'nationality'];

  public static $rules = [
    'identity_card' => 'required|unique:clients,identity_card|numeric',
    'first_name' => 'required',
    'last_name' => 'required',
    'nationality' => 'required|in:V,E'
  ];

  public static $customMessages = [
    'identity_card.required' => 'La cedula es obligatoria',
    'identity_card.unique' => 'Ya existe cliente con esta cedula',
    'first_name.required' => 'El nombre es obligatorio',
    'last_name.required' => 'El apellido es obligatorio',
    'nationality.required' => 'La nacionalidad es obligatoria',
    'nationality.in' => 'La nacionalidad es inválida'
  ];
}
