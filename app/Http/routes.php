<?php

//Route Type
Route::post('types', 'TypeController@store');
Route::put('types/{typeId}', 'TypeController@update');