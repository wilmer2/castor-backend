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
    'active_impost' => 1,
    'impost' => 12
  ];
});

$factory->defineAs(App\Models\Role::class, 'admin', function (Faker\Generator $faker) {
   return [
     'name' => 'admin',
     'display_name' => 'administrador'
   ];
});

$factory->defineAs(App\Models\Role::class, 'user', function (Faker\Generator $faker) {
   return [
     'name' => 'user',
     'display_name' => 'usuario'
   ];
});

$factory->defineAs(App\Models\Role::class, 'super', function (Faker\Generator $faker) {
   return [
     'name' => 'super',
     'display_name' => 'super administrador'
   ];
});

$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
   return [
     'name' => $faker->name,
     'email' => $faker->email,
     'password' => bcrypt('123456')
   ];
});