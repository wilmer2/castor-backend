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
     Route::post('clients', 'ClientController@store'); 
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

     //Route Client Reseervation
     Route::post('clients/{clientId}/reservations', 'ReservationController@store');

     //Route Reservation
     Route::get('rentals/reservation/pending', 'ReservationController@index');
     Route::put('rentals/{rentalId}/reservation_hour', 'ReservationController@updateReservationForHour');
     Route::put('rentals/{rentalId}/reservation_date', 'ReservationController@updateReservationForDate');
     Route::post('rentals/{rentalId}/confirm', 'ReservationController@confirmReservation');
     Route::get('rentals/{rentalId}/rooms_date/{startDate}/{endDate}/{time}', 'ReservationController@getAvailableDateRoom');
     Route::get('rentals/{rentalId}/rooms_hour/{startDate}/{startTime}/{endTime}', 'ReservationController@getAvailableHourRoom');
     Route::get('rentals/reservation/{startDate}/{endDate}', 'ReservationController@getReservation');
     //Route Setting
     Route::get('setting', 'SettingController@getSetting');
     Route::put('setting', 'SettingController@update');
   
     //Route Moves
     Route::get('moves/{startDate}/{endDate}', 'MoveController@moves');

     //Route User
     Route::get('users', 'UserController@index');
     Route::post('users', 'UserController@store');
     Route::get('users/{userId}', 'UserController@show');
     Route::put('users/{userId}', 'UserController@update');
     Route::post('users/{userId}/active', 'UserController@active');
  });
  

  


/*use App\Models\Rental;
use App\Models\Move;

Route::get('test', function () {
    $rental = Rental::find(19);
    $move = $rental->move()->first();  

    var_dump($move->date);
    exit();
});*/


/*use App\Models\Room;
Route::get('test', function () {
  $s = currentDate();
  $t = '2016-08-20';
  $days = diffDays($s, $t);
  dd($days, $s);
});*/

/*use App\Models\Room;

Route::get('test', function () {
  $rooms = Room::orderBy('created_at', 'desc')
  ->get();

  $find = $rooms->where('id', '343')
  ->first();

  dd($find);

 //return $t->code_number;

  dd($find[0]['id']);

  $find->code_number = 'hb-1A';
  $find->save();
  return $find;
});*/
/*use App\Models\Rental;

Route::get('test', function () {
  $rental = Rental::find(1);
  

  $rental->records()->update(['arrival_date ' => '2016-08-08']);
  
  //return $client;

});*/

/*use App\Models\User;
use App\Models\Move;
use App\Models\Rental;

Route::get('test', function () {
   $date = currentDate();*/

   /*$data = Move::join('users', 'moves.user_id', '=', 'users.id')
   ->with(['rentals' => function ($q) {
      $q->select('id');
   }])
   ->where('date', $date)
   ->get();*/

   /*$data = Move::join('users', 'moves.user_id', '=', 'users.id')
   ->join('rentals', 'moves.id', '=', 'rentals.move_id')
   ->select('moves.date', 'users.name')*/
   /*->selectRaw('
       (SELECT COUNT(*) FROM rentals WHERE rentals.type = "hours" AND rentals.move_id = moves.id) as num_hours,
       (SELECT COUNT(*) FROM rentals WHERE rentals.type = "days" AND rentals.move_id = moves.id) as num_days,
       (SELECT SUM(amount) FROM rentals WHERE rentals.type = "hours" AND rentals.move_id = moves.id) as amout_hour
      '   
    )*/ 
   /*->selectRaw('
      (SELECT COUNT(*) FROM rentals INNER JOIN rental_room ON rental_room.rental_id = rentals.id WHERE rental_room.check_out IS NOT NULL AND rentals.move_id = moves.id) as  test
    ')
   ->groupBy('moves.user_id', 'moves.date')
   ->get();*/
   /*$data = Move::with('rentals')->get();

   'rentals' => function ($q) {
      $q->select('arrival_date');
   }*/

   /*return $data;
});*/



/*use App\Models\Rental;

Route::get('test', function () {
  $date = currentDate();

  $data = Rental::
  selectRaw(
    '(SELECT COUNT(*) FROM rentals WHERE rentals.type = "hours") as num_hours,
     (SELECT COUNT(*) FROM rentals WHERE rentals.type = "days") as num_days,
     (SELECT SUM(amount) FROM rentals WHERE rentals.type = "hours") as amout_hour

    '
    )
  ->first();

  return $data;
});*/

/*use App\Models\Type;

Route::get('test', function () {
  $data = Type::with(['rooms.rentals' => function ($q) {
      $q->where('type', 'days');
  }])->first();

  dd($data);

  return $data;
});*/


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



