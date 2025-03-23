<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->foreignId('model_option_id')->after('user_id')->constrained('model_options')->onDelete('cascade');
            $table->string('title')->after('model_option_id')->default('New Chat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['model_option_id']);
            $table->dropColumn(['model_option_id', 'title']);
        });
    }
};
