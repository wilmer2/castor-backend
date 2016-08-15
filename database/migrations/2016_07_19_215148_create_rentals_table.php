<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRentalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('client_id')->unsigned();
            $table->integer('move_id')->unsigned()->nullable();
            $table->time('arrival_time')->nullable();
            $table->time('departure_time')->default('12:00:00');
            $table->date('arrival_date');
            $table->date('departure_date')->nullable();
            $table->date('checkout_date')->nullable();
            $table->enum('payment_type', ['transferencia', 'punto', 'efectivo']);
            $table->string('state');
            $table->enum('type', ['hours', 'days']);
            $table->float('amount');
            $table->float('amount_impost');
            $table->float('amount_total');
            $table->float('discount');
            $table->boolean('checkout');
            $table->boolean('reservation');
            $table->boolean('date_hour');
            
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
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
        Schema::drop('rentals');
    }
}
