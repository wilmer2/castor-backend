<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;

class Audit extends Ardent {
  protected $fillable = ['message'];
}