<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\DTOs;

final readonly class ExportRunContextDTO
{
    /**
     * Хранит пользовательский и корреляционный контекст одного export run.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
    ) {}
}
