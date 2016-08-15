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
        .$this->getCountRentalsDayWithHours(). 'as num_days_hour,'
        .$this->getTotalTransfer(). 'as  total_transfer,'
        .$this->getTotalTransferWithImpost(). 'as total_transfer_impost,'
        .$this->getTotalEffective(). 'as total_effective,'
        .$this->getTotalEffectiveWithImpost(). 'as total_effective_impost,'
        .$this->getTotalPoint(). 'as total_point,'
        .$this->getTotalPointWithImpost(). 'as total_point_impost,'
        .$this->getTotalDay(). 'as total_day,'
        .$this->getTotalDayWithImpost(). 'as total_day_impost'

      )
      ->groupBy('moves.user_id', 'moves.date');
  }

  public function getCountRentalsDays() {
    return '(
      SELECT COUNT(*) FROM rentals WHERE rentals.move_id = moves.id AND rentals.type = "days"
      AND rentals.date_hour = 0
    )';
  }

  public function getCountRentalsHours() {
    return '(SELECT COUNT(*) FROM rentals WHERE rentals.type = "hours" AND rentals.move_id = moves.id)';
  }

  public function getCountRentalsDayWithHours() {
    return '(
      SELECT COUNT(*) FROM rentals WHERE rentals.move_id = moves.id AND rentals.type = "days"
      AND rentals.date_hour = 1
    )';
  }

  public function getTotalTransfer() {
    return '(
      SELECT SUM(amount) FROM records WHERE records.move_id = moves.id AND 
      records.payment_type = "transferencia"
    )';
  }

  public function getTotalTransferWithImpost() {
    return '(
      SELECT SUM(amount_total) FROM records WHERE records.move_id = moves.id AND
      records.payment_type = "transferencia"
    )';
  }

  public function getTotalEffective() {
    return '(
      SELECT SUM(amount) FROM records WHERE records.move_id = moves.id AND
      records.payment_type = "efectivo"
    )';
  }

  public function getTotalEffectiveWithImpost() {
    return '(
      SELECT SUM(amount_total) FROM records WHERE records.move_id = moves.id AND
      records.payment_type = "efectivo"
    )';
  }

  public function getTotalPoint() {
    return '(
      SELECT SUM(amount) FROM records WHERE records.move_id = moves.id AND 
      records.payment_type = "punto"
    )';
  }

  public function getTotalPointWithImpost() {
    return '(
      SELECT SUM(amount_total) FROM records WHERE records.move_id = moves.id AND
      records.payment_type = "punto"
    )';
  }

  public function getTotalDay() {
    return '(
      SELECT SUM(amount) FROM records WHERE records.move_id = moves.id
    )';
  }

  public function getTotalDayWithImpost() {
    return '(
      SELECT SUM(amount_total) FROM records WHERE records.move_id = moves.id
    )';
  }


}
