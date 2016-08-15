<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('records', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('rental_id')->unsigned();
            $table->integer('move_id')->unsigned()->nullable();
            $table->string('type');
            $table->string('payment_type');
            $table->boolean('first');
            $table->date('arrival_date');
            $table->date('departure_date')->nullable();
            $table->time('departure_time');
            $table->float('amount');
            $table->float('amount_total');
            $table->boolean('conciliate');
            
            $table->foreign('rental_id')->references('id')->on('rentals')->onDelete('cascade');
            $table->foreign('move_id')->references('id')->on('moves');
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
        Schema::drop('records');
    }
}
