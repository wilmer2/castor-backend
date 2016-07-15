<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Validators\RentalValidator;
use Validator;

class CustomValidationRules extends ServiceProvider {

   /**
    * Bootstrap the application services.
    *
    * @return void
    */
   public function boot() {
      Validator::extend('date_hour', function ($attribute, $value, $paramaters, $validator) {
        $arrivalDate = array_get($validator->getData(), 'arrival_date', null);
        $rentalValidator = new RentalValidator();

        return $rentalValidator->isValidTime($arrivalDate, $value);
      });
   }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
      //
    }

}