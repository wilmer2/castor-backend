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
        .$this->getCountReservationHours(). 'as num_reservation_hour,'
        .$this->getCountReservationDays(). 'as num_reservation_day,'
        .$this->getTotalHours(). 'as total_hour,'
        .$this->getTotalDays(). 'as total_day,'
        .$this->getTotalDaysHours(). 'as total_day_hour,'
        .$this->getTotalReservatioHours(). 'as total_reservation_hour,'
        .$this->getTotalReservationDays(). 'as total_reservation_days,'
        .$this->getTotalHoursImpost(). 'as total_hour_impost,'
        .$this->getTotalDaysImpost(). 'as total_day_impost,'
        .$this->getTotalDaysHoursImpost(). 'as total_dayhour_impost,'
        .$this->getTotalReservatioHoursImpost(). 'as total_reservationhour_impost,'
        .$this->getTotalReservationDaysImpost(). 'as total_reservationday_impost'
      )
      ->groupBy('moves.user_id', 'moves.date');
  }

  public function getCountRentalsDays() {
    return '(
      SELECT COUNT(*) FROM rentals WHERE rentals.move_id = moves.id AND rentals.type = "days"
      AND rentals.date_hour = 0 AND rentals.reservation = 0
    )';
  }

  public function getCountRentalsHours() {
    return '(
      SELECT COUNT(*) FROM rentals WHERE rentals.type = "hours" AND rentals.move_id = moves.id
      AND rentals.reservation = 0
    )';
  }

  public function getCountRentalsDayWithHours() {
    return '(
      SELECT COUNT(*) FROM rentals WHERE rentals.move_id = moves.id AND rentals.type = "days"
      AND rentals.date_hour = 1
    )';
  }

  public function getCountReservationHours() {
    return '(
      SELECT COUNT(*) FROM rentals WHERE rentals.type = "hours" AND rentals.move_id = moves.id
      AND rentals.reservation = 1 AND rentals.state = "conciliado"
    )';
  }

  public function getCountReservationDays() {
    return '(
      SELECT COUNT(*) FROM rentals WHERE rentals.type = "days" AND rentals.move_id = moves.id
      AND rentals.reservation = 1 AND rentals.state = "conciliado"
    )';
  }

  public function getTotalHours() {
    return '(
      SELECT SUM(amount) FROM rentals WHERE rentals.type = "hours"
      AND rentals.move_id = moves.id
      AND rentals.reservation = 0
    )';
  }

  public function getTotalDays() {
    return '(
      SELECT SUM(amount) FROM rentals WHERE rentals.type = "days"
      AND rentals.move_id = moves.id AND rentals.date_hour = 0
      AND rentals.reservation = 0
    )';
  }

  public function getTotalDaysHours() {
    return '(
      SELECT SUM(amount) FROM  rentals WHERE rentals.type = "days"
      AND rentals.move_id = moves.id AND rentals.date_hour = 1
      AND rentals.reservation = 0
    )';
  }

  public function getTotalReservationDays() {
    return '(
      SELECT SUM(amount) FROM rentals WHERE rentals.type = "days"
      AND rentals.move_id = moves.id AND rentals.reservation = 1
      AND rentals.state = "conciliado"
    )';
  }

  public function getTotalReservatioHours() {
    return '(
      SELECT SUM(amount) FROM  rentals WHERE rentals.type = "hours"
      AND rentals.move_id = moves.id AND rentals.reservation = 1
      AND rentals.state = "conciliado"
    )';
  }

  public function getTotalDaysImpost() {
    return '(
      SELECT SUM(amount_total) FROM rentals WHERE rentals.type = "days"
      AND rentals.move_id = moves.id AND rentals.date_hour = 0
      AND rentals.reservation = 0
    )';
  }

  public function getTotalHoursImpost() {
    return '(
      SELECT SUM(amount_total) FROM rentals WHERE rentals.type = "hours"
      AND rentals.move_id = moves.id AND rentals.reservation = 0
    )';
  }

  public function getTotalDaysHoursImpost() {
    return '(
      SELECT SUM(amount_total) FROM rentals WHERE rentals.type = "days"
      AND rentals.move_id = moves.id AND rentals.date_hour = 1
    )';
  }

  public function getTotalReservationDaysImpost() {
    return '(
      SELECT SUM(amount_total) FROM rentals WHERE rentals.type = "days"
      AND rentals.move_id = moves.id AND rentals.reservation = 1
      AND rentals.state = "conciliado"
    )';
  }

  public function getTotalReservatioHoursImpost() {
    return '(
      SELECT SUM(amount_total) FROM  rentals WHERE rentals.type = "hours"
      AND rentals.move_id = moves.id AND rentals.reservation = 1
      AND rentals.state = "conciliado"
    )';
  }

}
