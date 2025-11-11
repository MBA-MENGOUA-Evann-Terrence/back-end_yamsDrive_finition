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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Le destinataire de la notification
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade'); // L'expéditeur de l'action
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade'); // Le document concerné
            $table->string('type'); // Ex: 'document_shared', 'comment_added'
            $table->text('message'); // Le message de la notification
            $table->timestamp('read_at')->nullable(); // Date et heure de lecture
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
