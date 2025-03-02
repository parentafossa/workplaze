<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('d365_voucher_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number');
            $table->string('tax_invoice_number')->nullable();
            $table->string('voucher');
            $table->date('date');
            $table->boolean('year_closed')->default(false);
            $table->string('ledger_account');
            $table->string('account_name');
            $table->text('description');
            $table->string('currency', 10);
            $table->decimal('amount_in_transaction_currency', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_in_reporting_currency', 15, 2);
            $table->string('posting_type');
            $table->string('posting_layer');
            $table->string('vendor_account')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('customer_account')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('sort_key')->nullable();
            $table->string('job_id')->nullable();
            $table->string('bp_list')->nullable();
            $table->string('tax_invoice_number2')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('created_by');
            $table->datetime('created_date_and_time');
            $table->boolean('correction')->default(false);
            $table->boolean('crediting')->default(false);
            $table->string('currency2', 10)->nullable();
            $table->text('description2')->nullable();
            $table->integer('level')->default(0);
            $table->string('main_account')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('posting_type2')->nullable();
            $table->string('transaction_type2')->nullable();
            $table->timestamps();

            $table->index(['date']);
            $table->index(['journal_number']);
            $table->index(['voucher']);
            $table->index(['ledger_account']);
            $table->index(['vendor_account']);
            $table->index(['customer_account']);
        });

        Schema::create('d365_voucher_transaction_imports', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number');
            $table->string('tax_invoice_number')->nullable();
            $table->string('voucher');
            $table->date('date');
            $table->boolean('year_closed')->default(false);
            $table->string('ledger_account');
            $table->string('account_name');
            $table->text('description');
            $table->string('currency', 10);
            $table->decimal('amount_in_transaction_currency', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_in_reporting_currency', 15, 2);
            $table->string('posting_type');
            $table->string('posting_layer');
            $table->string('vendor_account')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('customer_account')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('sort_key')->nullable();
            $table->string('job_id')->nullable();
            $table->string('bp_list')->nullable();
            $table->string('tax_invoice_number2')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('created_by');
            $table->datetime('created_date_and_time');
            $table->boolean('correction')->default(false);
            $table->boolean('crediting')->default(false);
            $table->string('currency2', 10)->nullable();
            $table->text('description2')->nullable();
            $table->integer('level')->default(0);
            $table->string('main_account')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('posting_type2')->nullable();
            $table->string('transaction_type2')->nullable();
            $table->timestamps();

            $table->index(['date']);
            $table->index(['journal_number']);
            $table->index(['voucher']);
            $table->index(['ledger_account']);
            $table->index(['vendor_account']);
            $table->index(['customer_account']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('d365_voucher_transactions');
        Schema::dropIfExists('d365_voucher_transaction_imports');
    }
};
