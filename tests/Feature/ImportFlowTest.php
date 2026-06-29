<?php

declare(strict_types=1);

use Src\Domain\Import\Exceptions\UnknownProcessor;
use Src\Domain\Import\FileProcessor;
use Src\Domain\Import\ProcessorRegistry;
use Src\Infrastructure\Import\Processors\TesteProcessor;

it('descobre os processadores disponíveis no registry', function () {
    $registry = app(ProcessorRegistry::class);

    expect($registry->all())->toContain(TesteProcessor::class)
        ->and($registry->options())->toHaveKey(TesteProcessor::class)
        ->and($registry->make(TesteProcessor::class))->toBeInstanceOf(FileProcessor::class);
});

it('rejeita um processador não registrado', function () {
    app(ProcessorRegistry::class)->make('App\\Naopossui');
})->throws(UnknownProcessor::class);
