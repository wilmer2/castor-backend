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
Route::put('rentals/{rentalId}/reservation_hour', 'RentalController@updateReservationForHour');
Route::get('rentals/{rentalId}/rooms_date', 'RentalController@getAvailableDateRoom');
Route::get('rentals/{rentalId}/rooms_hour', 'RentalController@getAvailableHourRoom');
