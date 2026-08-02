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
     */
    public function publish(LocalImportRequestDTO $request): bool;
}
