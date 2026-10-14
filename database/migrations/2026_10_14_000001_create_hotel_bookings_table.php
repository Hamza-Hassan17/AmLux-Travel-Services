<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('booking_ref', 30)->unique();          // e.g. HTL-DXB-98421
            $table->string('confirmation_no', 40)->nullable();    // the hotel's own confirmation number
            $table->string('crs_code', 40)->nullable();
            $table->string('voucher_no', 40)->nullable();
            $table->string('hotel_name');
            $table->unsignedTinyInteger('star_rating')->default(5);
            $table->string('city');
            $table->string('country')->nullable();
            $table->string('address')->nullable();
            $table->string('hotel_phone', 40)->nullable();
            $table->string('hotel_email')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->string('check_in_time', 5)->default('15:00');
            $table->string('check_out_time', 5)->default('12:00');
            $table->string('room_type');
            $table->string('bed_config')->nullable();
            $table->unsignedTinyInteger('rooms')->default(1);
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->string('meal_plan')->nullable();
            $table->string('special_request')->nullable();
            $table->text('amenities')->nullable();                // one per line, shown on the voucher
            $table->decimal('rate_per_night', 12, 2);
            $table->decimal('taxes', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->char('currency', 3)->default('PKR');
            $table->string('status', 20)->default('confirmed');   // confirmed|pending|cancelled
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotel_bookings');
    }
}
