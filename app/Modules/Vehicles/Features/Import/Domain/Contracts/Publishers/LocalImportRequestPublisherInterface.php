<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Publishers;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\LocalImportRequestDTO;

/**
 * Порт публикации локального Vehicles import request во внешний транспорт.
 */
interface LocalImportRequestPublisherInterface
{
    /**
     * Публикует запрос импорта.
     *
     * Шаги:
     * 1) Преобразовать local request DTO в outbound payload.
     * 2) Опубликовать payload во входящий import routing.
     * 3) Вернуть результат публикации.
     */
    public function publish(LocalImportRequestDTO $request): bool;
}
