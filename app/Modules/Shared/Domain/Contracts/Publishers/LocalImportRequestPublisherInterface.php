<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Contracts\Publishers;

use App\Modules\Shared\Domain\DTOs\LocalImportRequestDTO;

/**
 * Порт публикации локального import request во внешний транспорт.
 */
interface LocalImportRequestPublisherInterface
{
    /**
     * Публикует запрос импорта.
     */
    public function publish(LocalImportRequestDTO $request): bool;
}
