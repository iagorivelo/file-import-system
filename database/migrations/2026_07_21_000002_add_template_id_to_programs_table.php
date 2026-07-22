<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            // Quando preenchido, o programa roda em "modo configurável": aponta
            // para um ImportTemplate executado pelo TemplateProcessor. Quando
            // nulo, usa a classe processadora indicada em processor_class.
            $table->foreignId('template_id')
                ->nullable()
                ->after('processor_class')
                ->constrained('import_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
        });
    }
};
