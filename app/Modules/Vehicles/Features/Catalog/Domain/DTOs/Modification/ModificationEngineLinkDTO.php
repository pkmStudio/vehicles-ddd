<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Modification;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Хранит найденный двигатель и владельца связи engine_modification.
 */
final readonly class ModificationEngineLinkDTO
{
    /**
     * Инициализирует typed-снимок связи модификации с двигателем.
     */
    public function __construct(
        public EngineData $engine,
        public ProviderEnum $provider,
    ) {}
}
