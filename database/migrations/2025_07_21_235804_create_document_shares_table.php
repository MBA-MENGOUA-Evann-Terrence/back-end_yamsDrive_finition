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
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Utilisateur avec qui le document est partagé
            $table->foreignId('shared_by')->constrained('users')->onDelete('cascade'); // Utilisateur qui partage le document
            $table->enum('permission_level', ['read', 'edit'])->default('read');
            $table->string('token')->nullable()->unique(); // Pour le partage par lien
            $table->timestamp('expires_at')->nullable(); // Date d'expiration du partage
            $table->timestamps();
            
            // Empêcher les doublons de partage pour un même document et utilisateur
            $table->unique(['document_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};
