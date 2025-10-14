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
        // Ajouter la colonne uuid si elle n'existe pas déjà
        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique()->nullable();
        });

        // Générer un UUID pour les documents existants
        \DB::table('documents')->get()->each(function ($document) {
            \DB::table('documents')
                ->where('id', $document->id)
                ->update(['uuid' => (string) \Illuminate\Support\Str::uuid()]);
        });

        // Rendre la colonne non nullable après avoir rempli les valeurs
        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
