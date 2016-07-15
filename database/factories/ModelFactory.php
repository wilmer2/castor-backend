<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\Models\Setting::class, function (Faker\Generator $faker) {
   $minimumHour = '04:00';

   return [
    'price_day' => 3400,
    'price_hour' => 600,
    'time_minimum' => createHour($minimumHour),
    'active_impost' => 0,
    'impost' => 12
  ];
});
