<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\PartSpecificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;

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
        private PartSpecificationDataFactoryInterface $factory,
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

        $specification = $this->factory->make((int) $engine->id, $details);

        return $this->partSpecs->upsert($specification);
    }
}
