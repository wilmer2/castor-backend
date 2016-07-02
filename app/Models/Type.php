<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Type extends Ardent {

  protected $fillable = ['title', 'description', 'increment'];

  public static $rules = [
    'title' => 'required|unique:types,title',
    'description' => 'required',
    'increment' => 'numeric'
  ];

  public static $customMessages = [
    'title.required' => 'El tipo es obligatorio',
    'title.unique' => 'El tipo de ya ha sido registrado',
    'description' => 'La descripción es obligatoria',
    'increment.numeric' => 'El cargo extra debe ser un número'
  ];

}