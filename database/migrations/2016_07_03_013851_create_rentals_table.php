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
            $table->time('arrival_time')->nullable();
            $table->time('departure_time')->default('12:00');
            $table->date('arrival_date');
            $table->date('departure_date')->nullable();
            $table->date('cancel_date')->nullable();
            $table->enum('payment_type', ['transferencia', 'credito', 'efectivo']);
            $table->string('state');
            $table->enum('type', ['hours', 'days']);
            $table->float('amount');
            $table->boolean('checkout');
            $table->boolean('reservation');
            
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
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
