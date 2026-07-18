<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification;

/**
 * Передает параметры удаления PartSpecification из внешнего сообщения.
 */
final readonly class DeletePartSpecificationRequestDTO
{
    /**
     * Инициализирует immutable-снимок запроса удаления спеки.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $id,
    ) {}
}
