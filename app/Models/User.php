<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Zizaco\Entrust\Traits\EntrustUserTrait;

class User extends Authenticatable {

    use EntrustUserTrait;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'active'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function moves() {
      return $this->hasMany(Move::class);
    }

     public function loadRole() {
       if($this->hasRole('admin')) {
         $this->role = 1;
       } elseif ($this->hasRole('super')) {
         $this->role = 3;
       } else {
         $this->role = 2;
       }
    }
}
    