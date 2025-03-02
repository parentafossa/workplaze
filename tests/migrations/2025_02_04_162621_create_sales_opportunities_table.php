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
        Schema::create('sales_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('customer_name');
            $table->decimal('estimated_value', 15, 2);
            $table->date('expected_closing_date');
            $table->enum('status', ['new', 'in_progress', 'quotation_phase', 'won', 'lost']);
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        Schema::create('sales_activities', function (Blueprint $table) {
            $table->id();
            $table->string('activity_type');
            $table->text('description');
            $table->datetime('date');
            $table->text('outcome')->nullable();
            $table->string('next_action')->nullable();
            $table->datetime('next_action_date')->nullable();
            $table->foreignId('sales_opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->date('validity_start_date');
            $table->date('validity_end_date');
            $table->enum('status', ['draft', 'sent', 'confirmed', 'rejected', 'expired']);
            $table->text('notes')->nullable();
            $table->foreignId('sales_opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('sales_activities');
        Schema::dropIfExists('sales_opportunities');
    }
};
