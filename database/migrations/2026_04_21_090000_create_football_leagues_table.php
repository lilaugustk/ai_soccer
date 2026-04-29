<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_leagues', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // API ID
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('logo')->nullable();
            $table->string('country_name')->nullable();
            $table->string('country_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_leagues');
    }
};
