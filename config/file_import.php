<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Processadores de arquivo
    |--------------------------------------------------------------------------
    |
    | Diretório e namespace onde ficam as classes que implementam o contrato
    | Src\Domain\Import\FileProcessor. O registry varre este diretório para
    | montar a lista de processadores disponíveis ao cadastrar um programa.
    |
    */
    'processors' => [
        'directory' => base_path('src/Infrastructure/Import/Processors'),
        'namespace' => 'Src\\Infrastructure\\Import\\Processors',
    ],

    /*
    |--------------------------------------------------------------------------
    | Atividade do usuário
    |--------------------------------------------------------------------------
    |
    | Janela (em minutos) para considerar um usuário "online" a partir da
    | última atividade registrada.
    |
    */
    'online_threshold_minutes' => (int) env('ONLINE_THRESHOLD_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Armazenamento dos arquivos importados
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'disk' => env('IMPORTS_DISK', 'local'),
        'directory' => 'imports',
    ],

    // Tamanho máximo do arquivo de importação, em kilobytes.
    'max_file_size_kb' => (int) env('IMPORTS_MAX_FILE_SIZE_KB', 51200),

    /*
    |--------------------------------------------------------------------------
    | Exportações (destino "arquivo de exportação")
    |--------------------------------------------------------------------------
    |
    | Diretório onde o motor configurável grava os arquivos normalizados quando
    | o destino do template é "arquivo de exportação".
    |
    */
    'exports' => [
        'directory' => storage_path('app/exports'),
    ],

];
