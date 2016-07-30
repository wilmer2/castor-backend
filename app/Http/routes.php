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
Route::get('rentals/{rentalId}/rooms_date', 'ReservationController@getAvailableDateRoom');
Route::get('rentals/{rentalId}/rooms_hour', 'ReservationController@getAvailableHourRoom');
Route::post('rentals/{rentalId}/room/{roomId}/checkout', 'RentalController@checkoutRoom');
Route::post('rentals/{rentalId}/checkout', 'RentalController@checkout');
Route::post('rentals/{rentalId}/add_room', 'RentalController@addRooms');
Route::post('rentals/{rentalId}/room/{roomId}/remove', 'RentalController@removeRoom');

//Route Reservation
Route::post('rentals/reservation', 'ReservationController@addReservation');
Route::put('rentals/{rentalId}/reservation_hour', 'ReservationController@updateReservationForHour');
Route::put('rentals/{rentalId}/reservation_date', 'ReservationController@updateReservationForDate');


/*use App\Models\Rental;

Route::get('test', function () {
  $roomsId = [3, 5];

  $rental = Rental::find(1);
  $date = currentDate();
  $pivotData = array_fill(0, count($roomsId), ['check_in' => $date]);
  $syncData = array_combine($roomsId, $pivotData);
  

  $rental->rooms()->sync($syncData, false);

 

  /*dd($rooms);


  $speakers  = (array) Input::get('speakers'); // related ids
  $pivotData = array_fill(0, count($speakers), ['is_speaker' => true]);
  $syncData  = array_combine($speakers, $pivotData);

  $user->roles()->sync($syncData);

});*/
use Carbon\Carbon;

Route::get('test', function () {
  $currentDate = currentDate();
  $date = Carbon::parse($currentDate);
  $anotherDate = Carbon::parse($currentDate);


  $days = $date->diff($anotherDate)->days;

  dd($days);
});



