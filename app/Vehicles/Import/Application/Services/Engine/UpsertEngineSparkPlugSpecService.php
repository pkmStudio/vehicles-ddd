<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Engine;

use App\Vehicles\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Vehicles\Import\Domain\ModelData\PartSpecificationData;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use App\Templates\Domain\Enums\DetailTemplateEnum;

/**
 * Use-case: создать/обновить спецификацию «свечи зажигания» для двигателя по eng_id.
 * Сборка details из строки — забота адаптера (парсинг по шаблону); здесь — резолв
 * двигателя через Repository и запись спецификации через Command.
 */
final readonly class UpsertEngineSparkPlugSpecService implements UpsertEngineSparkPlugSpecServiceInterface
{
    public function __construct(
        private EngineRepositoryInterface $engines,
        private PartSpecificationCommandInterface $partSpecs,
    ) {}

    /**
     * @param  array<string, mixed>  $details  собранные значения спецификации
     */
    public function upsertByEngine(int $engId, array $details): ?PartSpecificationData
    {
        $engine = $this->engines->firstByEngId($engId);

        if (! $engine) {
            return null;
        }

        $specification = new PartSpecificationData(
            partableType: PartableTypeEnum::ENGINE->value,
            partableId: (int) $engine->id,
            template: DetailTemplateEnum::SPARK_PLUGS,
            details: $details,
        );

        return $this->partSpecs->upsert($specification);
    }
}
