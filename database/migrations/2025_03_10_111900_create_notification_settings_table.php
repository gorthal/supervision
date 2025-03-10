<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_new')->default(true);
            $table->boolean('notify_error')->default(true);
            $table->boolean('notify_warning')->default(true);
            $table->boolean('notify_info')->default(false);
            $table->string('frequency')->default('immediate');
            $table->foreignId('project_id')->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
