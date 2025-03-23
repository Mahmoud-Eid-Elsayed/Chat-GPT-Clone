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
        Schema::create('model_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_provider_id')->constrained();
            $table->string('name');
            $table->string('model_id');
            $table->text('description');
            $table->boolean('supports_vision')->default(false);
            $table->boolean('supports_file_input')->default(false);
            $table->boolean('supports_image_generation')->default(false);
            $table->boolean('supports_tts')->default(false);
            $table->boolean('supports_stt')->default(false);
            $table->boolean('supports_fine_tuning')->default(false);
            $table->boolean('supports_streaming')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_options');
    }
};