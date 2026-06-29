<?php

declare(strict_types=1);

arch('todo o código de domínio/aplicação/infra usa strict types')
    ->expect('Src')
    ->toUseStrictTypes();

arch('o domínio é puro (sem framework, app, aplicação ou infraestrutura)')
    ->expect('Src\Domain')
    ->not->toUse([
        'Illuminate',
        'Filament',
        'App',
        'Src\Application',
        'Src\Infrastructure',
    ]);

arch('a aplicação não conhece a camada de apresentação')
    ->expect('Src\Application')
    ->not->toUse([
        'Filament',
        'App',
    ]);

arch('nada de funções de debug no código-fonte')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'vd'])
    ->not->toBeUsed();
