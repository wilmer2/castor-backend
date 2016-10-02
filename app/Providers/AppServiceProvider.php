<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Client;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Audit;
use App\Models\Type;
use App\Models\User;
use Auth;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {  
       Client::created(function ($event) {
          $notification = ' ha registrado el cliente id: '.$event->id;
          
          $this->createAudit($notification);
       });

       Client::updated(function ($event) {
          $notification = ' ha actualizado al cliente id:'.$event->id;

          $this->createAudit($notification);
       });

       Client::deleted(function ($event) {
         $notification = ' ha eliminado al cliente id:'.$event->id;

         $this->createAudit($notification);
       });

       Rental::created(function ($event) {
          $notification = ' ha registrado el hospedaje id: '.$event->id;
          
          $this->createAudit($notification);
       });

       Rental::updated(function ($event) {
          $notification = ' ha actualizado el hospedaje id: '.$event->id;
          
          $this->createAudit($notification);
       });

       Rental::deleted(function ($event) {
          $notification = ' ha eliminado el hospedaje id: '.$event->id;
          
          $this->createAudit($notification);
       });

       Room::created(function ($event) {
          $notification = ' ha registrado la habitación id: '.$event->id;
          
          $this->createAudit($notification);
       });

       Room::updated(function ($event) {
          $notification = ' ha actualizado la habitación id: '.$event->id;
          
          $this->createAudit($notification);
       });

       Room::deleted(function ($event) {
          $notification = ' ha eliminado la habitación id: '.$event->id;

          $this->createAudit($notification);
       });

       Type::created(function ($event) {
          $notification = ' ha registrado el tipo id: '.$event->id;

          $this->createAudit($notification);
       });

       Type::updated(function ($event) {
          $notification = ' ha actualizado el tipo id: '.$event->id;

          $this->createAudit($notification);
       });

       Type::deleted(function ($event) {
          $notification = 'ha eliminado el tipo id: '.$event->id;

          $this->createAudit($notification);
       });

       User::created(function ($event) {
          $notification = ' ha registrado el usuario id:'. $event->id;

          $this->createAudit($notification);
       });

       User::updated(function ($event) {
          $notification = ' ha actualizado el usuario id:'. $event->id;

          $this->createAudit($notification);
       });
    }

    public function createAudit($notification) {
      if(auth()->check()) {
          $user = currentUser();
          $message = 'El usuario '.$user->name.$notification;

          Audit::create(['message' => $message]);
      }

    }


    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() == 'local') {
          $this->app->register('Laracasts\Generators\GeneratorsServiceProvider');
        }
    }
}
