<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Services;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;

/**
 * Описывает порт сервисной операции мутаций каталога.
 */
interface CatalogMutationNotificationServiceInterface
{
    /**
     * Публикует результат мутации каталога наружу.
     *
     * Шаги:
     * 1) Собрать транспортное RabbitMQ-сообщение.
     * 2) Передать сообщение publisher-адаптеру.
     */
    public function notify(CatalogMutationResultDTO $result): void;
}
