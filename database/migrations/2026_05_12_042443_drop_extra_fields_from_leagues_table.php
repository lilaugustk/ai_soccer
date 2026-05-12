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
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn(['type', 'logo_url', 'country_name', 'country_code', 'updated_at']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['logo_url', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name');
            $table->string('logo_url')->nullable()->after('type');
            $table->string('country_name')->nullable()->after('country');
            $table->string('country_code', 10)->nullable()->after('country_name');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('is_women');
            $table->timestamp('updated_at')->nullable();
        });
    }
};
