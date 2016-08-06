<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;
use App\Validators\RentalValidator;
use App\Events\RentalWasAssigned;

class Rental extends Ardent {
  
  public $autoPurgeRedundantAttributes = true;
  protected $rooms_validation = [];
  protected $old_departure = null;
  protected $extra_hour = null;

  protected $fillable = [
    'client_id',
    'arrival_time',
    'departure_time',
    'arrival_date',
    'departure_date',
    'checkout_date',
    'payment_type',
    'type',
    'amount',
    'room_ids',
    'renovate_hour',
    'state',
    'reservation',
    'checkout',
    'discount'
  ];

  public static $rules = [
    'room_ids' => 'required|exists:rooms,id',
    'payment_type' => 'required|in:transferencia,credito,efectivo',
    'type' => 'required|in:hours,days',
    'arrival_date' => 'required_if:reservation,1|date',
    'arrival_time' => 'required_if:reservation,1|date_format:H:i:s|date_hour',
    'departure_date' => 'required_if:type,days|date|after:arrival_date',
    'renovate_hour' => 'sometimes|required|in:01:00:00,02:00:00,03:00:00,04:00:00',
    'departure_time' => 'date_format:H:i:s',
    'discount' => 'numeric'
  ];

  public static $customMessages = [
    'arrival_date.required_if' => 'La fecha de ingreso es obligatoria',
    'arrival_date.date' => 'La fecha de ingreso es inválida',
    'arrival_date.after' => 'La fecha ya paso',
    'departure_date.required_if' => 'La fecha de salida es obligatoria',
    'departure_date.date' => 'La fecha de salida es inválida',
    'departure_date.after' => 'La fecha de salida debe ser mayor a la fecha de entrada',
    'arrival_time.required_if' => 'La hora de ingreso es obligatoria',
    'arrival_time.date_format' => 'La hora de ingreso es inválida',
    'arrival_time.date_hour' => 'La hora ya paso',
    'departure_time.date_format' => 'La hora de salida es inválida',
    'type.required' => 'El tipo es obligatorio',
    'type.in' => 'El tipo es inválido',
    'renovate_hour.required' => 'La hora de renovacion es obligatoria',
    'renovate_hour.in' => 'La hora para renovar es inválida',
    'payment_type.required' => 'El tipo de pago es obligatorio',
    'payment_type.in' => 'El tipo de pago no esta entre la opciones',
    'extra_hour.date_format' =>  'La hora de finalización es inválida', 
    'room_ids.required' => 'Las habitaciones son obligatorias',
    'room_ids.exists' => 'Alguna de las habitaciones no existe ',
    'discount.numeric' => 'El descuento debe ser un número'
  ];

  public function __construct($attributes = array()) {
    parent::__construct($attributes);

    $this->purgeFilters[] = function($key) {
        $purge = ['room_ids', 'renovate_hour'];
        return ! in_array($key, $purge);
    };
  }

  public function client() {
    return $this->belongsTo(Client::class);
  }

  public function rooms() {
    return $this->belongsToMany(Room::class)
     ->withTimestamps()
     ->withPivot('check_in', 'check_out', 'check_timeout');
  }
  
  public function move() {
    return $this->belongsTo(Move::class);
  }

  public function records() {
    return $this->hasMany(Record::class);
  }

  /** Model Events */

  public function afterValidate() {
    //Se introduce valores ya que al purgar la pierden su valor
    $this->rooms_validation = $this->room_ids;
     
    if($this->renovate_hour != null) {
      $this->extra_hour = $this->renovate_hour;
    }
  }

  public function beforeSave() {
    $valid = true;
    $rentalValidator = new RentalValidator();

    if(!$this->isValidArrivalDate($rentalValidator)) {
        $this->validationErrors->add('arrival_date', 'La fecha ya paso');
        $valid = false;
    }

    if(!$this->isValidRenovateDate($rentalValidator)) {
        $this->validationErrors->add('departure_date', 'La fecha para renovar deber ser mayor a la de salida');
        $valid = false;
    }

    if($valid && !$this->isValidTimeRooms($rentalValidator)) {
        $valid = false;
    }

    return $valid;
  }

  public function moveDispatch() {
    event(new RentalWasAssigned($this));
  }

  public function isValidArrivalDate($rentalValidator) {
    if($this->reservation) {
        return $rentalValidator->isValidDate($this->arrival_date);
    } else {
        return true;
    }
  }

  public function isValidRenovateDate($rentalValidator) {
    $valid = true;
    
    if($this->old_departure != null) {
        $valid = $rentalValidator->isValidBetweenDates(
           $this->old_departure, 
           $this->departure_date
        );
    }

    return $valid;
  }

  public function isValidTimeRooms($rentalValidator) {
    $hourFrom = $this->getHourFrom();

    if($this->extra_hour != null || $this->type == 'hours') {
        $this->validationErrors->add('departure_time', 'Algunas de las habitaciones ya estan ocupadas por estas horas');
        $dateFrom = $this->getDateFrom();
        
        $this->setDepartureTime($hourFrom);
        $this->setDepartureDate($dateFrom, $hourFrom);
        
        $valid = $rentalValidator->isValidRoomHour(
           $this->rooms_validation,
           $hourFrom,
           $this->departure_time,
           $dateFrom,
           $this->departure_date,
           $this->id
        );

    } else {
        $this->validationErrors->add('departure_date', 'Algunas de las habitaciones ya estan ocupadas por esta fechas');

        $valid = $rentalValidator->isValidRoomDate(
           $this->rooms_validation,
           $this->arrival_date,
           $this->departure_date,
           $hourFrom,
           $this->old_departure,
           $this->id
        );
     }

     return $valid;
  }

  public function addDateTime() {
    if($this->arrival_time == null && !$this->reservation) {
        $this->arrival_date = currentDate();
        $this->arrival_time = currentHour();
    }
      
  }

  public function getHourFrom() {
    if($this->extra_hour != null) {
        $hourFrom = $this->departure_time;
    } else {
        $hourFrom = $this->arrival_time;
    }

    return $hourFrom;
  }

  public function setDepartureTime($hourFrom) {
    if($this->extra_hour != null) {
        $this->departure_time = sumHour($hourFrom, $this->extra_hour);
        $this->extra_hour = null;
    } else {
        $setting = getSetting();

        if($this->departure_time == null) {
            $this->departure_time = sumHour($hourFrom, $setting->time_minimum);
        }
    }

  }

  public function getDateFrom() {
     if($this->departure_date == null) {
        return $this->arrival_date;
    } else {
        return $this->departure_date;
    }
  }

  public function setDepartureDate($dateFrom, $hourFrom) {
    if(
        $hourFrom > $this->departure_time && 
        $this->departure_date == null
    ) { 
        $this->departure_date = addDay($dateFrom);
    }
  }

  public function setOldDeparture() {
    if($this->departure_date == null) {
        $this->old_departure = $this->arrival_date;
    } else {
        $this->old_departure = $this->departure_date;
    }
  }

  public function isCheckout() {
    if($this->reservation) {
        return false;
    }

    $currentDate = currentDate();

    if(
        $this->departure_date == null && 
        $this->arrival_date < $currentDate ||
        $this->departure_date != null &&
        $this->departure_date < $currentDate
    ) {  
        $this->checkout = 1;
        $this->forceSave();
    }

    return $this->checkout;
  }

  public function isTimeout() {
    $date = currentDate();
    $hour = currentHour();
    
    if(!$this->reservation) {
        if(
            $this->departure_date == null && 
            $this->departure_time < $hour ||
            $this->departure_date == $date  &&
            $this->departure_time < $hour
        ) {
            return true;
        }
    }

    return false;
  }

  public function reservationExpired() {
    $currentDate = currentDate();
    
    if(
        $this->reservation &&
        $this->departure_date != null &&
        $this->departure_date < $currentDate ||
        $this->reservation &&
        $this->departure_date == null &&
        $this->arrival_date < $currentDate
      ) {
        return true;
    } else {
        return false;
    }
  }

  public function registerRecord() {
    $record = $this->records()
    ->where('first', 1);

    if($record->count() == 1) {
        $record = $record->first();
    } else {
        $record = new Record();
        $record->first = 1;
    }

    $this->setRecord($record);
  }

  public function setRecord($record) {
    if($this->departure_time == null) {
        $record->departure_time = createHour('12:00:00');
    } else {
        $record->departure_time = $this->departure_time;
    }

    $record->arrival_date = $this->arrival_date;
    $record->departure_date = $this->departure_date;
    
    $record->type = $this->type;
    $record->rental_id = $this->id;

    $record->save();
  }

  public function confirmCheckoutRoom() {
     $enabledRooms = $this->rooms()
     ->wherePivot('check_out', null);

     if($enabledRooms->count() == 0) {
        $this->checkout = 1;
        $this->forceSave();
     }
  }

  /** Model Querys */

  public function findRoom($roomId) {
    return $this->rooms()
    ->where('id', $roomId)
    ->first();
  }

  public function getRoomsId() {
    return $this->rooms()
    ->lists('id')
    ->toArray();
  }

  public function getRoomsCheckout() {
    return $this->rooms()
    ->wherePivot('check_out', '<>', null);
  }

  public function getRoomsIdCheckout() {
    return $this->getRoomsCheckout()
    ->lists('id')
    ->toArray();
  }
}