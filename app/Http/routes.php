<?php

//Route Clinets
Route::post('clients', 'ClientController@store');
Route::put('clients/{clientId}', 'ClientController@update');

//Route Types
Route::post('types', 'TypeController@store');
Route::put('types/{typeId}', 'TypeController@update');

//Route Rooms
Route::post('rooms', 'RoomController@store');
Route::put('rooms/{roomId}', 'RoomController@update');

//Route Rental
Route::post('rentals', 'RentalController@store');
Route::post('rentals/reservation', 'RentalController@addReservation');
Route::get('rentals/{rentalId}/available/rooms_date', 'RentalController@getAvailableDateRoom');



/*Route::get('test', function () {

  //$minutesRest = date('H:i', strtotime('-'.$minutes.' minutes', $hour));

    $hour = createHour('23:00');
    $sumHour = createHour('04:30');

    $time = explode(':', $sumHour);
     
    //date('H:s:i', strtotime('08:30:00 + 8 hours + 30 minutes'));

     $t = date('H:i', strtotime($hour. '+ '. $time[0].' hours'));
     $m = date('H:i', strtotime($t. '+'.$time[1].' minutes'));

     dd($m);
    
});*/

/*Route::get('test', function () {
  $currentHour = date('H:i');

  dd($currentHour);
});*/
/*use Carbon\Carbon;
Route::get('test', function () {

  dd(Carbon::today()->format('Y-m-d'));
  $date = '2015-07-06';

  $currentDate =  Carbon::parse($date)->addDay()->format('Y-m-d');
  dd($currentDate);
});*/

use App\Models\Room;
use App\Models\Rental;

Route::get('test', function () {

  /*$setting = getSetting();
  $startDate = '2016-07-15';
  $startHour = '02:00';
  $endHour = sumHour($startHour, $setting->time_minimum);

  $rooms = Room::hourRooms($startDate, $startHour, $endHour, 1)->get();*/

  /*$arrivalDate = '2016-07-22'; 
  $departureDate = '2016-07-26';  
  $arrivalHour = '02:00';
  $rentalId = 1;

  $rooms = Room::dateRooms(
       $arrivalDate,
       $departureDate,
       $arrivalHour,
       $rentalId
    )->get();*/

  $setting = getSetting();

  $arrivalDate = '2016-07-16';
  $departureDate = '2016-07-17';
  $arrivalHour = '23:00';
  $departureHour = sumHour($arrivalHour, $setting->time_minimum);
  $rentalId = 1;

  $rooms = Room::hourRoomInterval(
    $arrivalDate, 
    $departureDate, 
    $arrivalHour, 
    $departureHour, 
    $rentalId
  )->get();
   
  return $rooms;
});