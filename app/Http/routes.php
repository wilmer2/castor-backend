<?php


//Route login
Route::post('login', 'LoginController@login');
Route::post('logout', 'LoginController@logout');

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
Route::post('rentals/{rentalId}/checkout', 'RentalController@checkout');
Route::post('rentals/{rentalId}/room/{roomId}/remove', 'RentalController@removeRoom');
Route::post('rentals/{rentalId}/room/{roomId}/change', 'RentalController@changeRoom');
Route::post('rentals/{rentalId}/add_rooms/date', 'RentalController@addRoomsDate');
Route::post('rentals/{rentalId}/add_rooms/hour', 'RentalController@addRoomsHour');
Route::post('rentals/{rentalId}/room/{roomId}/checkout_date', 'RentalController@checkoutRoomDate');
Route::post('rentals/{rentalId}/room/{roomId}/checkout_hour', 'RentalController@checkoutRoomHour');
Route::put('rentals/{rentalId}/renovate_hour', 'RentalController@renovateHour');
Route::put('rentals/{rentalId}/renovate_date', 'RentalController@renovateDate');
Route::post('rentals/{rentalId}/cancel', 'RentalController@cancel');
Route::delete('rentals/{rentalId}', 'RentalController@delete');




//Route Reservation
Route::post('rentals/reservation', 'ReservationController@addReservation');
Route::get('rentals/{rentalId}/rooms_date', 'ReservationController@getAvailableDateRoom');
Route::get('rentals/{rentalId}/rooms_hour', 'ReservationController@getAvailableHourRoom');
Route::put('rentals/{rentalId}/reservation_hour', 'ReservationController@updateReservationForHour');
Route::put('rentals/{rentalId}/reservation_date', 'ReservationController@updateReservationForDate');




/*use App\Models\Client;


use App\Models\Rental;

Route::get('test', function () {
  $rental = Rental::find(1);

  $rooms = $rental->rooms()
  ->whereRaw('check_in = check_out')
  ->lists('id')
  ->toArray();

  dd($rooms);
});*/

/*Route::get('test', function () {
  $dogs = ['alegria' => ['age' => 12]];
  $people = ['andrea' => ['age' => 20], 'kelly' => ['age' => 19]];
  
  $array = array_collapse([$dogs , $people]);
  dd($array);
});*/


/*use Carbon\Carbon;

Route::get('test', function () {
   $date =  new Carbon(currentDate());
   $afterDate = new Carbon(addDay($date));
   $afterDate = new Carbon(addDay($afterDate));

   $t = $date->diff($afterDate)->days;

   dd($t, $date, $afterDate);
});*/

/*Route::get('test', function () {
   $arr = [4 => ['age' => 23]];
   $brr = [3 => ['age' => 22]];
   

   $n = array_keys($arr);

   dd($union);
});*/





/*use App\Models\Room;

Route::get('test', function () {
  $rooms = Room::whereDoesntHave('rentals', function ($q) {
    $q->where('arrival_date', '>=', '2016-07-30')
      ->where('departure_date', '<=', '2016-08-02')
      ->where('check_out', null);
  })
  ->get();

  return $rooms;

});*/

/*use App\Models\Room;

Route::get('test', function () {
   
   $roomsIds = [1, 2, 3];
   $t = 'test';
    $rooms = Room::whereDoesntHave('rentals', function ($q) {
      $q->where('check_out', null);
    })
    ->whereIn('rooms.id', $roomsIds)
    ->get();

    
    return $rooms;
});*/



//
/*use App\Models\Rental;

Route::get('test', function () {
  //$roomsId = [3, 5];

  //rental = Rental::find(1);
  /*$date = currentDate();
  $pivotData = array_fill(0, count($roomsId), ['check_in' => $date]);
  $syncData = array_combine($roomsId, $pivotData);
  
        
        dd($pivotData, $syncData);
  //$rental->rooms()->sync($syncData, false);
  $d = 1;

  $arr = ['check_in', '2016-20-07'];

  $l = array_combine($d, $arr);

  dd($l);
 

  dd($rooms);


  $speakers  = (array) Input::get('speakers'); // related ids
  $pivotData = array_fill(0, count($speakers), ['is_speaker' => true]);
  $syncData  = array_combine($speakers, $pivotData);

  $user->roles()->sync($syncData);

});*/
/*use Carbon\Carbon;

Route::get('test', function () {
  $currentDate = currentDate();
  $date = Carbon::parse($currentDate);
  $anotherDate = Carbon::parse($currentDate);


  $days = $date->diff($anotherDate)->days;

  dd($days);
});*/



