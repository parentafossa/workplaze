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
        Schema::create('trello_lists', function (Blueprint $table) {
            $table->id();
            $table->string('trello_id')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('trello_cards', function (Blueprint $table) {
            $table->id();
            $table->string('trello_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('trello_list_id');
            $table->string('business_area')->nullable(); // TD10, TD50, TD90
            $table->boolean('is_urgent')->default(false);
            $table->json('labels')->nullable();
            $table->json('metadata')->nullable(); // For additional data
            $table->timestamps();

            $table->foreign('trello_list_id')
                ->references('id')
                ->on('trello_lists')
                ->onDelete('cascade');
        });

        Schema::create('trello_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('trello_id')->unique();
            $table->unsignedBigInteger('trello_card_id');
            $table->string('name');
            $table->string('url');
            $table->string('mime_type')->nullable();
            $table->bigInteger('bytes')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamps();

            $table->foreign('trello_card_id')
                ->references('id')
                ->on('trello_cards')
                ->onDelete('cascade');
        });

        Schema::create('trello_syncs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_sync')->nullable();
            $table->integer('cards_created')->default(0);
            $table->integer('cards_updated')->default(0);
            $table->integer('attachments_synced')->default(0);
            $table->text('log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trello_attachments');
        Schema::dropIfExists('trello_cards');
        Schema::dropIfExists('trello_lists');
        Schema::dropIfExists('trello_syncs');
    }
};
