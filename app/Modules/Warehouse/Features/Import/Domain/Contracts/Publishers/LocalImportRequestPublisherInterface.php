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
     *
     * Шаги:
     * 1) Принять DTO локального файла и routing metadata.
     * 2) Собрать сообщение внешнего import request.
     * 3) Опубликовать сообщение и вернуть признак успешной публикации.
     */
    public function publish(LocalImportRequestDTO $request): bool;
}
