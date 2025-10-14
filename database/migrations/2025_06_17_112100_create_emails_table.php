<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

   public function up(): void
   {
      Schema::create('emails', function (Blueprint $table) {
         $table->id();
         $table->string('objet')->nullable();
         $table->string('message')->nullable();
         $table->string('statut')->nullable();
         $table->string('type')->nullable();
         $table->date('date_envoi')->nullable();
         $table->date('date_lecture')->nullable();
         $table->string('prospect_id')->nullable();
         $table->string('user_id')->nullable();
         $table->timestamps();
         $table->softDeletes();
      });
   }


   public function down(): void
   {
      Schema::dropIfExists('emails');
   }
};
