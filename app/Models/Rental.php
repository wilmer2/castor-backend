<?php

namespace App\Models;

use LaravelArdent\Ardent\Ardent;
use App\Validators\RentalValidator;

class Rental extends Ardent {
  
  public $autoPurgeRedundantAttributes = true;
  protected $roomsValidation = [];
  protected $old_departureDate = null;

  protected $fillable = [
    'client_id',
    'arrival_time',
    'departure_time',
    'arrival_date',
    'departure_date',
    'cancel_date',
    'payment_type',
    'type',
    'amount',
    'room_ids',
    'extra_hour',
    'state',
    'reservation'
  ];

  public static $rules = [
    'room_ids' => 'required|exists:rooms,id',
    'payment_type' => 'required|in:transferencia,credito,efectivo',
    'type' => 'required|in:hours,days',
    'arrival_date' => 'required_if:reservation,1|date',
    'arrival_time' => 'required_if:reservation,1|date_format:H:i|date_hour',
    'departure_date' => 'required_if:type,days|date|after:arrival_date',
    'extra_hour' => 'sometimes|date_format:H:i',
    'departure_time' => 'date_format:H:i'
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
    'type.required' => 'El tipo es obligatorio',
    'type.in' => 'El tipo es inválido',
    'payment_type.required' => 'El tipo de pago es obligatorio',
    'payment_type.in' => 'El tipo de pago no esta entre la opciones',
    'extra_hour.date_format' =>  'La hora de finalización es inválida', 
    'room_ids.required' => 'Las habitaciones son obligatorias',
    'room_ids.exists' => 'Alguna de las habitaciones no existe '
  ];

  public function __construct($attributes = array()) {
    parent::__construct($attributes);

    $this->purgeFilters[] = function($key) {
        $purge = ['room_ids', 'extra_hour'];
        return ! in_array($key, $purge);
    };
  }

  public function client() {
    return $this->belongsTo(Client::class);
  }

  public function rooms() {
    return $this->belongsToMany(Room::class);
  }

  /** Model Events */

  public function afterValidate() {
    //Se introduce array ya que al purgar la propiedad pierde su valor
    $this->roomsValidation = $this->room_ids;
  }

  public function beforeSave() {
    $valid = true;
    $rentalValidator = new RentalValidator();

    $this->addDateTime();

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

  public function isValidArrivalDate($rentalValidator) {
    if($this->reservation) {
        return $rentalValidator->isValidDate($this->arrival_date);
    } else {
        return true;
    }
  }

  public function isValidRenovateDate($rentalValidator) {
    $response = true;

    if($this->old_departureDate != null) {
        $response = $rentalValidator->isValidBetweenDates(
           $this->old_departureDate, 
           $this->departure_date
        );
    }

    return $response;
  }

  public function isValidTimeRooms($rentalValidator) {
    $hourFrom = $this->getHourFrom();

    if($this->extra_hour != null || $this->type == 'hours') {
        $this->validationErrors->add('departure_time', 'Algunas de las habitaciones ya estan ocupadas por estas horas');
        $dateFrom = $this->getDateFrom();

        $this->setDepartureTime($hourFrom);
        $this->setDepartureDate($dateFrom, $hourFrom);

        $response = $rentalValidator->isValidRoomHour(
           $this->roomsValidation,
           $hourFrom,
           $this->departure_time,
           $dateFrom,
           $this->departure_date,
           $this->id
        );

    } else {
        $this->validationErrors->add('departure_date', 'Algunas de las habitaciones ya estan ocupadas por esta fechas');
          
        $response = $rentalValidator->isValidRoomDate(
           $this->roomsValidation,
           $this->arrival_date,
           $this->departure_date,
           $hourFrom,
           $this->old_departureDate,
           $this->id
        );
     }

     return $response;
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
       
    } else {
        $setting = getSetting();

        if($this->departure_time == null) {
            $this->departure_time = sumHour($hourFrom, $setting->time_minimum);
        }
    }

  }

  public function getDateFrom() {
     if($this->departure_date == null) {
        $dateFrom = $this->arrival_date;
    } else {
        $dateFrom = $this->departure_date;
    }

    return $dateFrom;
  }

  public function setDepartureDate($dateFrom, $hourFrom) {
    if($hourFrom > $this->departure_time) {
        $this->departure_date = addDay($dateFrom);
    }
  }

  public function getRoomsId() {
    return $this->rooms()
    ->lists('id')
    ->toArray();
  }
}