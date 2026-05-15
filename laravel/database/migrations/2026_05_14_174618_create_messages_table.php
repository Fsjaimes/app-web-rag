<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('conversation_id')
                  ->constrained('conversations')
                  ->onDelete('cascade');

            // user      → mensaje del estudiante
            // assistant → respuesta del asistente IA
            $table->enum('role', ['user', 'assistant']);

            $table->text('content'); // texto del mensaje

            // Fragmentos de documentos que usó el RAG para responder
            // Solo se llena cuando role = assistant
            $table->json('sources')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};