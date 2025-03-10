<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('slack_webhook_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_on_error')->default(true);
            $table->boolean('notify_on_warning')->default(true);
            $table->boolean('notify_on_info')->default(false);
            $table->string('notification_frequency')->default('immediate');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
