<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Engine;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Engine\UpdateEngineRequestDTO;

/**
 * Описывает порт сценария мутации двигателей из внешнего сообщения.
 */
interface UpdateEngineUseCaseInterface
{
    /**
     * Выполняет сценарий мутации двигателей.
     *
     * Шаги:
     * 1) Зафиксировать operationId для идемпотентности.
     * 2) Проверить бизнес-ограничения операции.
     * 3) Выполнить запись через Command и отправить доменное событие.
     * 4) Опубликовать унифицированный результат мутации.
     */
    public function execute(UpdateEngineRequestDTO $request): ?CatalogMutationResultDTO;
}
