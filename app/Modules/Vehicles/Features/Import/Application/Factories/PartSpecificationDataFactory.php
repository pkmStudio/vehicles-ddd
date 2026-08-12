<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\PartSpecificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Собирает engine spark-plug part specification data для import-сценариев.
 */
final readonly class PartSpecificationDataFactory implements PartSpecificationDataFactoryInterface
{
    /**
     * Возвращает typed `PartSpecificationData` для спецификации свечей двигателя.
     *
     * Шаги:
     * 1) Зафиксировать владельца specification как engine.
     * 2) Зафиксировать details template как spark plugs.
     * 3) Передать details и optional internal id в typed data object.
     *
     * @param  array<string, mixed>  $details
     */
    public function make(int $engineId, array $details, ?int $id = null): PartSpecificationData
    {
        return new PartSpecificationData(
            partableType: PartableTypeEnum::ENGINE->value,
            partableId: $engineId,
            template: DetailTemplateEnum::SPARK_PLUGS,
            details: $details,
            id: $id,
        );
    }
}
