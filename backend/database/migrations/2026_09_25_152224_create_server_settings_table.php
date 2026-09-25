<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_settings', function (Blueprint $table) {
            $table->id();

            $table->string('settings');
            $table->text('value')->nullable();

            $table->foreignId('update_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique('settings');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_settings');
    }
};