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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('requires_validity_control')->default(false);
            $table->integer('notification_days_before')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id');
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('document_number')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('file_path');
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            
            $table->foreign('customer_id')->references('id')->on('m_customers');
        });

        Schema::create('document_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // business_area or business_type
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_document_document_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('customer_documents');
        Schema::dropIfExists('customer_document_document_tag');
        Schema::dropIfExists('document_tags');
    }
};
