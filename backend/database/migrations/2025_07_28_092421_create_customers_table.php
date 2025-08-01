<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            // ELIMINAMOS user_id - los clientes son independientes
            $table->string('name');
            $table->string('email'); // Permitir duplicados ya que no están vinculados a usuarios
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Spain');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Índices para búsqueda
            $table->index('email');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};