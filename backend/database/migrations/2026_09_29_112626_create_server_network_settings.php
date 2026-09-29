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
        Schema::create('server_network_settings', function (Blueprint $table) {
            $table->id();

            $table->jsonb('dns')->default('{}');
            $table->jsonb('proxy')->default('{}');
            $table->jsonb('docker_proxy')->default('{}');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_network_settings');
    }
};
