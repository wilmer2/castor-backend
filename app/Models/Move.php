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

  public function records() {
    return $this->hasMany(Record::class);
  }

  /** Model Querys */
  public function scopeSelectMoves($query) {
    return $query->join('users', 'moves.user_id', '=', 'users.id')
     ->join('rentals', 'moves.id', '=', 'rentals.move_id');
  }

  public function scopeAmountMove($query) {
    return $query->selectMoves()
    ->select('users.name', 'moves.date')
    ->selectRaw(
         $this->getCountRentalsDays() . 'as num_days,'
        .$this->getCountRentalsHours(). 'as num_hours,'
        .$this->getTotalHours(). 'as total_hour,'
        .$this->getTotalDays(). 'as total_day,'
        .$this->getTotalHoursImpost(). 'as impost_hour,'
        .$this->getTotalDaysImpost(). 'as impost_day'
      )
      ->groupBy('moves.user_id', 'moves.date');
  }

  public function checkRentals() {
    if($this->rentals()->count() == 0) {
        $this->delete();
    }
  }

  public function getCountRentalsDays() {
    return '(
      SELECT COUNT(*) FROM rentals WHERE rentals.move_id = moves.id AND rentals.type = "days"
      AND rentals.state = "conciliado"
    )';
  }

  public function getCountRentalsHours() {
    return '(
      SELECT COUNT(*) FROM rentals WHERE rentals.type = "hours" AND rentals.move_id = moves.id
      AND rentals.state = "conciliado"
    )';
  }

  public function getTotalHours() {
    return '(
      SELECT SUM(amount) FROM rentals WHERE rentals.type = "hours"
      AND rentals.move_id = moves.id
      AND rentals.state = "conciliado"
    )';
  }

  public function getTotalDays() {
    return '(
      SELECT SUM(amount) FROM rentals WHERE rentals.type = "days"
      AND rentals.move_id = moves.id AND rentals.state = "conciliado"
    )';
  }

  public function getTotalDaysImpost() {
    return '(
      SELECT SUM(amount_total) FROM rentals WHERE rentals.type = "days"
      AND rentals.move_id = moves.id AND rentals.state = "conciliado"
    )';
  }

  public function getTotalHoursImpost() {
    return '(
      SELECT SUM(amount_total) FROM rentals WHERE rentals.type = "hours"
      AND rentals.move_id = moves.id AND rentals.state = "conciliado"
    )';
  }

}
