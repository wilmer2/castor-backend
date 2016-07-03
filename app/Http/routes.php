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