<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // identificador público seguro

            $table->string('title');        // nombre descriptivo del documento
            $table->string('filename');     // nombre del archivo en disco
            $table->string('mime_type');    // application/pdf, etc.
            $table->unsignedBigInteger('size_bytes'); // peso del archivo

            // Estado del proceso de indexación en ChromaDB
            // pending   → recién subido, aún no procesado
            // processing→ FastAPI está procesando el documento
            // indexed   → disponible para consultas
            // error     → falló la indexación
            $table->enum('status', ['pending', 'processing', 'indexed', 'error'])
                  ->default('pending');

            $table->text('error_message')->nullable(); // detalle si status = error
            $table->json('chroma_ids')->nullable();    // IDs de chunks en ChromaDB

            // Quién subió el documento
            $table->foreignId('uploaded_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_documents');
    }
};