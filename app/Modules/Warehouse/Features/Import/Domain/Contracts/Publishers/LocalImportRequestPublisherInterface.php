<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Publishers;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\LocalImportRequestDTO;

/**
 * Порт публикации локального Warehouse import request во внешний транспорт.
 */
interface LocalImportRequestPublisherInterface
{
    /**
     * Публикует запрос импорта.
     */
    public function publish(LocalImportRequestDTO $request): bool;
}
