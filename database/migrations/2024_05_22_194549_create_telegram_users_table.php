<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->string('first_name')->nullable();
            $table->string('username')->nullable();
            $table->string('language_code', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id']);
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id');
            $table->unsignedBigInteger('chat_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('username')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats');
        Schema::dropIfExists('telegram_users');
    }
};
