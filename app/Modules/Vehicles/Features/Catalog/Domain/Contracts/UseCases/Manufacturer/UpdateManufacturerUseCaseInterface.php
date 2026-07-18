<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\UpdateManufacturerRequestDTO;

/**
 * Описывает порт сценария мутации производителей из внешнего сообщения.
 */
interface UpdateManufacturerUseCaseInterface
{
    /**
     * Выполняет сценарий мутации производителей.
     *
     * Шаги:
     * 1) Зафиксировать operationId для идемпотентности.
     * 2) Проверить бизнес-ограничения операции.
     * 3) Выполнить запись через Command и отправить доменное событие.
     * 4) Опубликовать унифицированный результат мутации.
     */
    public function execute(UpdateManufacturerRequestDTO $request): ?CatalogMutationResultDTO;
}
