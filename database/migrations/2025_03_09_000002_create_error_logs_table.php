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
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('environment')->nullable();
            $table->text('error_message')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('line')->nullable();
            $table->string('level')->nullable();
            $table->timestamp('error_timestamp')->nullable();
            $table->integer('occurrences')->default(1);
            $table->string('status')->default('new');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index pour améliorer la recherche et le filtrage
            $table->index(['project_id', 'status']);
            $table->index(['level', 'status']);
            $table->index('error_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
