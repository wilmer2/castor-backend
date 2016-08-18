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
      $rental = $event->rental;
      $date = currentDate();

      if($rental->move_id == null) {
          $user = currentUser();
          $move = Move::where('user_id', $user->id)
          ->where('date', $date);

          if($move->count() > 0) {
              $move = $move->first();
          } else {
              $move = new Move();
              $move->date = $date;
              $move->user_id = $user->id;

              $move->save();
          }
          
          /*$record = $rental->lastRecord();
          $record->move_id = $move->id;
          $record->save();*/

          $rental->move_id = $move->id;
          $rental->forceSave();
      }
    }
}
