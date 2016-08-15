<?php

namespace App\Listeners;

use App\Events\RentalWasAssigned;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Room;

class CheckRentalRooms
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

        if(!$rental->reservation) {
            if($rental->checkout) {
              $state = 'disponible';
            } else {
              $state = 'ocupada';
            }

            $rooms = $rental->getEnabledRooms()
            ->get();

            foreach ($rooms as $room) {
              $room->state = $state;
              $room->save();
            }

            $rental->stateRoomCheckout();
            $rental->rentalDayWithRoomsHour();
            
            $this->stateRoomWithoutRental();
        }
    }


    public function stateRoomWithoutRental() {
        $rooms = Room::whereDoesntHave('rentals')
        ->where('state', 'ocupada');

        if($rooms->count() > 0) {
            $rooms = $rooms->get();

            foreach ($rooms as $room) {
               $room->state = 'disponible';
               $room->save();
            }
        }
    }
}
