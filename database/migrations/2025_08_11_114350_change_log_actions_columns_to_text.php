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
        Schema::table('log_actions', function (Blueprint $table) {
            $table->text('nouvelles_valeurs')->nullable()->change();
            $table->text('anciennes_valeurs')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_actions', function (Blueprint $table) {
            $table->string('nouvelles_valeurs')->nullable()->change();
            $table->string('anciennes_valeurs')->nullable()->change();
        });
    }
};
