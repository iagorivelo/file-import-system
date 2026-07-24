<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Slug para deixar a URL do tenant amigável: /app/{slug} em vez de /app/{id}.
        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // Preenche as empresas já existentes com um slug único a partir do nome.
        $used = [];
        foreach (DB::table('companies')->get(['id', 'name']) as $company) {
            $base = Str::slug($company->name) ?: 'empresa';
            $slug = $base;
            $i = 2;

            while (in_array($slug, $used, true) || DB::table('companies')->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }

            $used[] = $slug;
            DB::table('companies')->where('id', $company->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
