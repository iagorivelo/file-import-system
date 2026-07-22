<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Escopo de tenancy nas entidades do domínio de importação. Nulo indica
     * recurso global/da plataforma (ex.: template de nicho reaproveitável).
     */
    public function up(): void
    {
        foreach (['programs', 'file_imports', 'import_templates'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('companies')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['programs', 'file_imports', 'import_templates'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
