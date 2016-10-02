<?php

header("Access-Control-Allow-Origin: http://castor");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS, DELETE');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

    //Route login
   Route::post('login', 'LoginController@login');
   Route::post('logout', 'LoginController@logout');
   
   Route::group(['middleware' => 'securitiy.login'], function () {
     //Route Client
     Route::get('clients', 'ClientController@index');
     Route::get('clients/identity_card/{identityCard}', 'ClientController@searchForIdentityCard');
     Route::get('clients/{clientId}/rentals', 'ClientController@getRentals');
     Route::get('clients/{clientId}/reservations', 'ClientController@getReservations');
     Route::post('clients', 'ClientController@store'); 
     Route::post('clients/search', 'ClientController@search');
     Route::get('clients/{clientId}', 'ClientController@show');
     Route::put('clients/{clientId}', 'ClientController@update');
     Route::delete('clients/{clientId}', 'ClientController@delete');

     //Route Types
     Route::get('types', 'TypeController@index');
     Route::post('types', 'TypeController@store'); 
     Route::get('types/{typeId}', 'TypeController@show');
     Route::put('types/{typeId}', 'TypeController@update');
     Route::delete('types/{typeId}', 'TypeController@delete');

     //Route Rooms
     Route::get('rooms', 'RoomController@index');
     Route::post('rooms', 'RoomController@store');
     Route::get('rooms/maintenance', 'RoomController@getRoomsMaintenance');
     Route::get('rooms/disabled', 'RoomController@getRoomsDisabled');
     Route::get('rooms/{roomId}', 'RoomController@show');
     Route::put('rooms/{roomId}', 'RoomController@update');
     Route::get('rooms/available_date/{startDate}/{endDate}/{time}', 'RoomController@availableDatesRooms');
     Route::get('rooms/available_date/add/{startDate}/{endDate}/{time}', 'RoomController@availableAddDatesRooms');
     Route::get('rooms/available_hour/{startDate}/{startTime}/{endTime}', 'RoomController@availableHourRooms');
     Route::get('rooms/available_hour/add/{startDate}/{startTime}/{endTime}', 'RoomController@availableAddHourRooms');
     Route::post('rooms/{roomId}/disabled', 'RoomController@disableRoom');
     Route::post('rooms/{roomId}/enable', 'RoomController@enableRoom');
     Route::delete('rooms/{roomId}', 'RoomController@delete');  

     //Route Client Rentals
     Route::post('clients/{clientId}/rentals', 'RentalController@store');

     //Route Rentals
     Route::get('rentals', 'RentalController@index');
     Route::get('rentals/{rentalId}', 'RentalController@show');
     Route::get('rentals/{rentalId}/enabled_rooms', 'RentalController@getRentalEnabledRooms');
     Route::post('rentals/{rentalId}/checkout', 'RentalController@checkout');
     Route::post('rentals/{rentalId}/room/{roomId}/change', 'RentalController@changeRoom');
     Route::post('rentals/{rentalId}/room/{roomId}/remove', 'RentalController@removeRoom');
     Route::post('rentals/{rentalId}/add_rooms/date', 'RentalController@addRoomsDate');
     Route::post('rentals/{rentalId}/add_rooms/hour', 'RentalController@addRoomsHour');
     Route::post('rentals/{rentalId}/room/{roomId}/checkout_date', 'RentalController@checkoutRoomDate');
     Route::post('rentals/{rentalId}/room/{roomId}/checkout_hour', 'RentalController@checkoutRoomHour');
     Route::put('rentals/{rentalId}/renovate_hour', 'RentalController@renovateHour');
     Route::put('rentals/{rentalId}/renovate_date', 'RentalController@renovateDate');
     Route::delete('rentals/{rentalId}', 'RentalController@delete');

     //Route Record
     Route::post('rentals/{rentalId}/records', 'RecordController@store');
     Route::get('records/{recordId}', 'RecordController@show');
     Route::put('records/{recordId}', 'RecordController@update');

     //Route Client Reservation
     Route::post('clients/{clientId}/reservations', 'ReservationController@store');

     //Route Reservation
     Route::get('rentals/reservation/pending', 'ReservationController@index');
     Route::put('rentals/{rentalId}/reservation_hour', 'ReservationController@updateReservationForHour');
     Route::put('rentals/{rentalId}/reservation_date', 'ReservationController@updateReservationForDate');
     Route::post('rentals/{rentalId}/confirm', 'ReservationController@confirmReservation');
     Route::get('rentals/{rentalId}/rooms_date/{startDate}/{endDate}/{time}', 'ReservationController@getAvailableDateRoom');
     Route::get('rentals/{rentalId}/rooms_hour/{startDate}/{startTime}/{endTime}', 'ReservationController@getAvailableHourRoom');
     Route::get('rentals/reservation/{startDate}/{endDate}', 'ReservationController@getReservation');

     //Route User Logged
     Route::get('users/logged', 'UserController@logged');

     //Route Setting
     Route::get('setting', 'SettingController@getSetting');

     Route::group(['middleware' => 'securitiy.admin'], function () {
        //Route Setting      
        Route::put('setting', 'SettingController@update');
   
        //Route Moves
        Route::get('moves/{startDate}/{endDate}', 'MoveController@moves');

        //Route User
        Route::get('users', 'UserController@index');
        Route::post('users', 'UserController@store');
        Route::get('users/{userId}', 'UserController@show');
        Route::put('users/{userId}', 'UserController@update');
        Route::post('users/{userId}/active', 'UserController@active');

        //Route Audit
        Route::get('audits', 'AuditController@index');
     });

     
  });
  

  






