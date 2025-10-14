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
        Schema::table('documents', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('document_shares', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('log_actions', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('document_shares', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('log_actions', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['action', 'created_at']);
        });
    }
};
