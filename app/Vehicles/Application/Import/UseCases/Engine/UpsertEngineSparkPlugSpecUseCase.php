<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Engine;

use App\Vehicles\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Models\PartSpecification;

/**
 * Use-case: создать/обновить спецификацию «свечи зажигания» для двигателя по eng_id.
 * Сборка details из строки — забота адаптера (парсинг по шаблону); здесь — резолв
 * двигателя через Repository и запись спецификации через Command.
 */
final readonly class UpsertEngineSparkPlugSpecUseCase implements \App\Vehicles\Domain\Contracts\Import\UseCases\Engine\UpsertEngineSparkPlugSpecUseCaseInterface
{
    public function __construct(
        private EngineRepositoryInterface $engines,
        private PartSpecificationCommandInterface $partSpecs,
    ) {}

    /**
     * @param  array<string, mixed>  $details  собранные значения спецификации
     * @return PartSpecification|null null, если двигатель с таким eng_id не найден
     */
    public function execute(int $engId, array $details): ?PartSpecification
    {
        $engine = $this->engines->firstByEngId($engId);

        if (! $engine) {
            return null;
        }

        return $this->partSpecs->upsert(new PartSpecificationData(
            partableType: Engine::class,
            partableId: $engine->id,
            template: DetailTemplateEnum::SPARK_PLUGS,
            details: $details,
        ));
    }
}
