<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Engine;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Engine\EngineMutationRequestDTO;

/**
 * Описывает порт сценария мутации двигателей из внешнего сообщения.
 */
interface StartEngineMutationUseCaseInterface
{
    /**
     * Запускает сценарий мутации двигателей по типу операции.
     *
     * Шаги:
     * 1) Определить операцию из DTO входящего сообщения.
     * 2) Преобразовать общий request в DTO конкретной операции.
     * 3) Делегировать выполнение профильному use case.
     */
    public function execute(EngineMutationRequestDTO $request): ?CatalogMutationResultDTO;
}
