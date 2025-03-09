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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->boolean('notify_new')->default(true);
            $table->boolean('notify_critical')->default(true);
            $table->boolean('notify_error')->default(true);
            $table->boolean('notify_warning')->default(false);
            $table->enum('frequency', ['realtime', 'hourly', 'daily'])->default('realtime');
            $table->time('daily_time')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
