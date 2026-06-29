<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Registro gerado pela importação do programa "Teste".
 *
 * @property int $id
 * @property string $nome
 * @property Carbon|null $created_at
 */
class TesteRecord extends Model
{
    protected $table = 'testes_tb';

    /**
     * A tabela possui apenas a data de criação.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'nome',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
