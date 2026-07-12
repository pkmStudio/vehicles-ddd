<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Modification;

use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Передает параметры сценария или результат мутации модификаций.
 */
final readonly class ModificationMutationRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных модификаций.
     */
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateModificationRequestDTO|UpdateModificationRequestDTO|DeleteModificationRequestDTO $request,
    ) {}
}
