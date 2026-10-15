<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounting core: invoices with service lines, receipts, payments, refunds,
 * suppliers, commission agents and cash/bank accounts.
 *
 * Existing flight/hotel invoices (one per booking) are converted into
 * single-line invoices and, when they were marked paid, a receipt is
 * created for them so cash totals stay consistent.
 */
class AccountingCore extends Migration
{
    public function up()
    {
        // ---- customers: walk-ins have no passport/email; opening balances ----
        DB::statement('ALTER TABLE customers MODIFY phone VARCHAR(30) NULL, MODIFY email VARCHAR(191) NULL, MODIFY passport_no VARCHAR(30) NULL, MODIFY nationality VARCHAR(60) NULL');
        Schema::table('customers', function (Blueprint $table) {
            $table->string('type', 20)->default('customer')->after('id');     // customer | walk_in
            $table->string('tax_no', 40)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('country', 60)->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);            // owed to us at start
        });

        // ---- masters ----
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);            // we owe them at start
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('bank');                      // cash | bank | gateway
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ---- invoices: become multi-line sales documents ----
        DB::statement('ALTER TABLE invoices MODIFY invoiceable_type VARCHAR(191) NULL, MODIFY invoiceable_id BIGINT UNSIGNED NULL');
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('bill_to_type', 20)->default('customer');          // customer | walk_in
            $table->date('due_date')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('agent_commission_mode', 10)->nullable();          // fixed | percent
            $table->decimal('agent_commission_value', 12, 2)->default(0);
            $table->decimal('agent_commission', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);                   // net of discount, before exclusive tax
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('buying_total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('service_type', 20);                               // flight | hotel
            $table->string('serviceable_type')->nullable();
            $table->unsignedBigInteger('serviceable_id')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('reference')->nullable();                          // PNR / booking reference
            $table->string('passenger')->nullable();
            $table->text('description')->nullable();
            $table->decimal('buying_price', 12, 2)->default(0);
            $table->text('buying_breakdown')->nullable();                     // JSON, flight fare components
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->string('tax_mode', 10)->default('none');                  // none | inclusive | exclusive
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('taxable_on_margin')->default(false);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->timestamps();
            $table->index(['serviceable_type', 'serviceable_id']);
        });

        // ---- bookings keep itinerary details; money lives on the invoice line ----
        DB::statement('ALTER TABLE flight_bookings MODIFY arrival_at DATETIME NULL');
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('passenger_name')->nullable();
            $table->string('passenger_mobile', 30)->nullable();
        });
        Schema::table('hotel_bookings', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_mobile', 30)->nullable();
        });

        // ---- money movements ----
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('amount', 12, 2);
            $table->date('received_on');
            $table->string('reference')->nullable();                          // cheque / transaction no
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->cascadeOnDelete();   // null = opening balance
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->unique();
            $table->string('payee_type', 20);                                 // supplier | customer (refund)
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('reference')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_no')->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_line_id')->constrained('invoice_lines')->cascadeOnDelete();
            $table->date('refunded_on');
            $table->decimal('supplier_refund', 12, 2)->default(0);            // received back from the supplier
            $table->decimal('margin', 12, 2)->default(0);                     // our charge, kept
            $table->decimal('customer_refund', 12, 2)->default(0);            // supplier_refund - margin
            $table->decimal('offset_amount', 12, 2)->default(0);              // taken off the unpaid invoice balance
            $table->decimal('payable_amount', 12, 2)->default(0);             // cash still owed to the customer
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_line_id')->nullable()->constrained('invoice_lines')->cascadeOnDelete(); // supplier payable
            $table->foreignId('refund_id')->nullable()->constrained('refunds')->cascadeOnDelete();             // customer refund
            $table->decimal('amount', 12, 2);
            $table->timestamps();                                              // both null = supplier opening balance
        });

        $this->backfill();
    }

    private function backfill()
    {
        $now = now();
        $cash = DB::table('accounts')->insertGetId(['name' => 'Cash', 'type' => 'cash', 'opening_balance' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('accounts')->insert(['name' => 'Bank', 'type' => 'bank', 'opening_balance' => 0, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $map = [
            'App\\Models\\FlightBooking' => ['table' => 'flight_bookings', 'service' => 'flight'],
            'App\\Models\\HotelBooking' => ['table' => 'hotel_bookings', 'service' => 'hotel'],
        ];

        foreach (DB::table('invoices')->whereNotNull('invoiceable_id')->get() as $inv) {
            if (! isset($map[$inv->invoiceable_type])) {
                continue;
            }
            $meta = $map[$inv->invoiceable_type];
            $booking = DB::table($meta['table'])->where('id', $inv->invoiceable_id)->first();
            if (! $booking) {
                continue;
            }
            $customer = DB::table('customers')->where('id', $inv->customer_id)->first();
            $reference = $meta['service'] === 'flight' ? $booking->pnr : $booking->booking_ref;
            $total = (float) $inv->total;
            $paid = $inv->payment_status === 'paid' ? $total : 0;

            DB::table($meta['table'])->where('id', $booking->id)->update(['invoice_id' => $inv->id]);
            DB::table('invoice_lines')->insert([
                'invoice_id' => $inv->id, 'service_type' => $meta['service'],
                'serviceable_type' => $inv->invoiceable_type, 'serviceable_id' => $booking->id,
                'reference' => $reference, 'passenger' => optional($customer)->name,
                'buying_price' => 0, 'selling_price' => $total, 'discount' => 0, 'service_charge' => 0,
                'tax_mode' => 'none', 'tax_rate' => 0, 'tax_amount' => 0, 'line_total' => $total,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('invoices')->where('id', $inv->id)->update([
                'subtotal' => $total, 'paid_amount' => $paid, 'due_date' => $inv->issued_on,
            ]);

            if ($paid > 0) {
                $receiptId = DB::table('receipts')->insertGetId([
                    'receipt_no' => 'RCT/L' . $inv->id, 'customer_id' => $inv->customer_id, 'account_id' => $cash,
                    'amount' => $paid, 'received_on' => $inv->issued_on, 'reference' => $inv->payment_method,
                    'remarks' => 'Migrated from the original booking invoice ' . $inv->invoice_no,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('receipt_allocations')->insert([
                    'receipt_id' => $receiptId, 'invoice_id' => $inv->id, 'amount' => $paid,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('receipt_allocations');
        Schema::dropIfExists('receipts');

        Schema::table('hotel_bookings', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['invoice_id', 'guest_name', 'guest_mobile']);
        });
        Schema::table('flight_bookings', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['invoice_id', 'passenger_name', 'passenger_mobile']);
        });
        Schema::dropIfExists('invoice_lines');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropColumn(['bill_to_type', 'due_date', 'agent_id', 'agent_commission_mode', 'agent_commission_value',
                'agent_commission', 'subtotal', 'tax_total', 'buying_total', 'paid_amount', 'refunded_amount', 'notes', 'user_id']);
        });
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('suppliers');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['type', 'tax_no', 'date_of_birth', 'country', 'opening_balance']);
        });
    }
}
