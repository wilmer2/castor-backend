<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRentalRoomPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_room', function (Blueprint $table) {
            $table->integer('rental_id')->unsigned()->index();
            $table->foreign('rental_id')->references('id')->on('rentals')->onDelete('cascade');
            $table->integer('room_id')->unsigned()->index();
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->primary(['rental_id', 'room_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('rental_room');
    }
}
