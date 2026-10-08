<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->morphs('invoiceable'); // flight / hotel / chauffeur booking
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('total', 12, 2);
            $table->char('currency', 3)->default('PKR');
            $table->string('payment_status', 20)->default('unpaid'); // unpaid|paid
            $table->string('payment_method')->nullable();
            $table->date('issued_on');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
