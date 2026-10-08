<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlightBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('flight_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('pnr', 20)->unique();
            $table->string('ticket_no', 30)->nullable();
            $table->string('airline');
            $table->string('flight_no', 20);
            $table->string('aircraft')->nullable();
            $table->string('origin', 3);
            $table->string('destination', 3);
            $table->string('origin_airport')->nullable();
            $table->string('destination_airport')->nullable();
            $table->dateTime('departure_at');
            $table->dateTime('arrival_at');
            $table->string('cabin_class', 30)->default('Economy');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->string('seat', 10)->nullable();
            $table->string('baggage')->nullable();
            $table->string('meal')->nullable();
            $table->decimal('base_fare', 12, 2);
            $table->decimal('taxes', 12, 2)->default(0);
            $table->decimal('fuel_surcharge', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->char('currency', 3)->default('PKR');
            $table->string('status', 20)->default('confirmed'); // confirmed|pending|cancelled
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('flight_bookings');
    }
}
