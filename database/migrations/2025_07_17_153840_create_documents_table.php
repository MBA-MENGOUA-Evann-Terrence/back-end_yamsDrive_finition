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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('chemin');
            $table->string('type');
            $table->unsignedBigInteger('taille');
            $table->text('description')->nullable();
            
            // Clé étrangère vers la table users
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Clé étrangère vers la table services (nullable car un document peut ne pas être lié à un service)
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes(); // Pour la suppression logique
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer d'abord les contraintes de clé étrangère
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['service_id']);
        });
        
        // Puis supprimer la table
        Schema::dropIfExists('documents');
    }
};
