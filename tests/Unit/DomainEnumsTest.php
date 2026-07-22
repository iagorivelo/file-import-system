<?php

declare(strict_types=1);

use Src\Domain\Import\Exceptions\UnsupportedFileType;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportResult;
use Src\Domain\Import\ImportStatus;
use Src\Domain\User\UserRole;

it('UserRole rotula e identifica administrador', function () {
    expect(UserRole::Admin->label())->toBe('Administrador')
        ->and(UserRole::Admin->isAdmin())->toBeTrue()
        ->and(UserRole::User->isAdmin())->toBeFalse()
        ->and(UserRole::options())->toBe([
            'admin' => 'Administrador',
            'user' => 'Usuário',
        ]);
});

it('FileType resolve por extensão e rejeita tipos inválidos', function () {
    expect(FileType::fromExtension('.CSV'))->toBe(FileType::Csv)
        ->and(FileType::fromExtension('txt'))->toBe(FileType::Txt)
        ->and(FileType::fromExtension('XLSX'))->toBe(FileType::Xlsx)
        ->and(FileType::allowedExtensions())->toBe(['txt', 'csv', 'xlsx']);

    expect(fn () => FileType::fromExtension('pdf'))
        ->toThrow(UnsupportedFileType::class);
});

it('ImportStatus indica término e cor do badge', function () {
    expect(ImportStatus::Completed->isFinished())->toBeTrue()
        ->and(ImportStatus::Processing->isFinished())->toBeFalse()
        ->and(ImportStatus::Pending->isInProgress())->toBeTrue()
        ->and(ImportStatus::Failed->color())->toBe('danger')
        ->and(ImportStatus::Completed->color())->toBe('success');
});

it('ImportResult agrega linhas e detecta erros', function () {
    $result = new ImportResult(processedRows: 8, failedRows: 2, errors: ['erro']);

    expect($result->totalRows())->toBe(10)
        ->and($result->hasErrors())->toBeTrue()
        ->and(ImportResult::empty()->hasErrors())->toBeFalse();
});
