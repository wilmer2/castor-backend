<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Ardent {

  use SoftDeletes;
  
  protected $fillable = ['identity_card', 'first_name', 'last_name', 'nationality', 'deleted_at'];

  public static $rules = [
    'identity_card' => 'required|unique:clients,identity_card',
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

  public function rentals() {
    return $this->hasMany(Rental::class);
  }

  public function scopeSearchForIdentityCard($query, $identityCard) {
    $client = $query->where('identity_card', $identityCard)->first();

    if($client) {
        return $client;
    } else {
        abort(404);
    }
  }

  public function scopeName($query, $name) {
    return $query->where(\DB::raw('CONCAT(first_name, " ", last_name)'), 'Like', '%'.$name.'%')
    ->take(5)
    ->get();
  }

  public function getReservation() {
    return $this->rentals()
    ->where('reservation', 1)
    ->orderBy('arrival_date', 'desc')
    ->get();
  }

  public function getRentals() {
    return $this->rentals()
    ->where('reservation', 0)
    ->orderBy('arrival_date', 'desc')
    ->get();
  }
}
