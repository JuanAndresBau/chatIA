<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('chat_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id'); // Para identificar la sesión de usuario
            $table->text('context'); // Para almacenar el historial
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_contexts');
    }
};
