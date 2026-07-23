<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Nicho do template (nulo = template de nicho global reaproveitável).
            $table->string('niche')->nullable()->index();
            // Configuração (dado, não código): formato de origem, campos com
            // mapeamento/transformações/validações e destino.
            $table->json('source_format');
            $table->json('fields');
            $table->json('destination');
            // Chave (key de campo) usada para deduplicar linhas dentro do arquivo.
            $table->string('dedup_key')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_templates');
    }
};
