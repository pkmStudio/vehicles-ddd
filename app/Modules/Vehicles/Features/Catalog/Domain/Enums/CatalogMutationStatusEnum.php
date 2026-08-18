<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Enums;

/**
 * Перечисляет допустимые значения потока мутаций каталога.
 */
enum CatalogMutationStatusEnum: string
{
    case Completed = 'completed';
    case CompletedWithErrors = 'completed_with_errors';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
