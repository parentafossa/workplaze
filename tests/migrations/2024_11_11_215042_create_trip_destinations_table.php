<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('model_type'); // The full class name of the model
            $table->json('steps');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('approval_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_id')->constrained();
            $table->morphs('approvable'); // This creates approvable_type and approvable_id
            $table->integer('current_step');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_instance_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('action'); // 'approve' or 'reject'
            $table->text('comments')->nullable();
            $table->integer('step_number');
            $table->timestamps();
        });
    }
};
