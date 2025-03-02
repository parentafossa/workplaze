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
        Schema::create('gwms_delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('system_id')->unique();
            $table->string('site_cd')->nullable();
            $table->string('site_name')->nullable();
            $table->string('owner_cd')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('st_no')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('etd')->nullable();
            $table->dateTime('eta')->nullable();
            $table->string('ship_to_cd')->nullable();
            $table->string('ship_to_name')->nullable();
            $table->text('ship_to_adr1')->nullable();
            $table->text('ship_to_adr2')->nullable();
            $table->string('sj_barcode')->nullable();
            $table->string('truck_cd')->nullable();
            $table->string('truck_name')->nullable();
            $table->string('truck_no')->nullable();
            $table->integer('ctn_qty')->nullable();
            $table->decimal('bulk_m3', 10, 2)->nullable();
            $table->decimal('wgt_kg', 10, 2)->nullable();
            $table->boolean('sj_receipt_print_flg')->default(false);
            $table->string('sj_receipt_print_flg_name')->nullable();
            $table->string('sj_receipt_print_user_id')->nullable();
            $table->string('sj_receipt_print_user_name')->nullable();
            $table->date('sj_receipt_print_date')->nullable();
            $table->time('sj_receipt_print_time')->nullable();
            $table->integer('sj_qty')->nullable();
            $table->date('sj_received_date')->nullable();
            $table->string('sj_received_user')->nullable();
            $table->integer('lt')->nullable();
            $table->date('due_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['system_id', 'site_cd', 'st_no'], 'gwms_delivery_notes_composite_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gwms_delivery_notes');
    }
};
