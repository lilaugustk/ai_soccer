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
        Schema::table('football_players', function (Blueprint $table) {
            $table->string('firstname')->nullable()->after('name');
            $table->string('lastname')->nullable()->after('firstname');
            $table->string('height')->nullable()->after('birth_year');
            $table->string('weight')->nullable()->after('height');
            $table->boolean('injured')->default(false)->after('weight');
            $table->integer('number')->nullable()->after('injured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('football_players', function (Blueprint $table) {
            $table->dropColumn(['firstname', 'lastname', 'height', 'weight', 'injured', 'number']);
        });
    }
};
