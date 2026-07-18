<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Передает параметры сценария или результат мутации двигателей.
 */
final readonly class EngineMutationRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных двигателей.
     */
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateEngineRequestDTO|UpdateEngineRequestDTO|DeleteEngineRequestDTO $request,
    ) {}
}
