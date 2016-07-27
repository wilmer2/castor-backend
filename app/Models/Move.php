<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Move extends Ardent {
  protected $fillable = ['user_id', 'date'];

  public function user() {
    return $this->belongsTo(User::class);
  }

  public function rentals() {
    return $this->hasMany(Rental::class);
  }
}
