<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('label')->nullable();
            $table->text('credentials');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
