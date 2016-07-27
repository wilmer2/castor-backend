<?php

namespace App\Listeners;

use App\Events\RentalWasAssigned;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Move;

class AssignToMove
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  RentalWasAssigned  $event
     * @return void
     */
    public function handle(RentalWasAssigned $event) {
      $move = $event->move();

      if(!$move) {
          if($event->state == 'conciliado') {
              $user = currentUser();

              $move = Move::firstOrCreate([
                 'date' => $event->arrival_date,
                 'user_id' => $user->id
              ]);
          }
      } else {
          if($event->state != 'conciliado') {
              //
          }
      }
    }
}
