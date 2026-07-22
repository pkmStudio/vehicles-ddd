<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Engine;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\PartSpecificationDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineSparkPlugSpecServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationCreated;
use App\Modules\Vehicles\Shared\Domain\Events\PartSpecification\PartSpecificationUpdated;

/**
 * Use-case: создать/обновить спецификацию «свечи зажигания» для двигателя по eng_id.
 * Сборка details из строки — забота адаптера (парсинг по шаблону); здесь — резолв
 * двигателя через Repository и запись спецификации через Command.
 */
final readonly class UpsertEngineSparkPlugSpecService implements UpsertEngineSparkPlugSpecServiceInterface
{
    private const int IMPORT_USER_ID = 0;

    private const string OPERATION_ID = 'vehicles-part-specification-import';

    public function __construct(
        private EngineRepositoryInterface $engines,
        private PartSpecificationCommandInterface $partSpecs,
        private PartSpecificationRepositoryInterface $specifications,
        private PartSpecificationDataFactoryInterface $factory,
    ) {}

    /**
     * @param  array<string, mixed>  $details  собранные значения спецификации
     */
    public function upsertByEngine(int $engId, array $details): ?PartSpecificationData
    {
        $engine = $this->engines->findByEngId($engId);

        if (! $engine) {
            return null;
        }

        $specification = $this->factory->make((int) $engine->id, $details);
        $existing = $this->specifications->findByPartableTemplateAndFeatureValue(
            partableType: $specification->partableType,
            partableId: $specification->partableId,
            template: $specification->template,
            featureValueId: $specification->featureValueId,
        );
        $specification = $existing === null
            ? $this->partSpecs->create($specification)
            : $this->partSpecs->update($this->factory->make((int) $engine->id, $details, $existing->id));

        event($existing === null
            ? new PartSpecificationCreated(self::IMPORT_USER_ID, self::OPERATION_ID, $specification->toArray())
            : new PartSpecificationUpdated(self::IMPORT_USER_ID, self::OPERATION_ID, $specification->toArray()));

        return $specification;
    }
}
