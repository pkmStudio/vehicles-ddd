<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\DTOs;

final readonly class ImportRunContextDTO
{
    /**
     * Хранит пользовательский и корреляционный контекст одного import run.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
    ) {}
}
