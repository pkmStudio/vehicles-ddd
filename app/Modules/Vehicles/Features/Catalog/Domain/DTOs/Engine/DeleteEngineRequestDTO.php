<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine;

/**
 * Передает параметры сценария или результат мутации двигателей.
 */
final readonly class DeleteEngineRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных двигателей.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $engId,
    ) {}
}
