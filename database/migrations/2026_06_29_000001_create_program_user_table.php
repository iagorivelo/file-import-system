<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vínculo de permissão entre usuários e programas: cada usuário comum só
     * enxerga (e importa) os programas que lhe foram liberados pelo admin.
     */
    public function up(): void
    {
        Schema::create('program_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['program_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_user');
    }
};
