<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class FeatureValueData extends Data
{
    /**
     * @param  int  $featureId  идентификатор справочной фичи
     * @param  string  $name  display name значения
     * @param  string|null  $shortCode  короткий код значения для export details
     * @param  int|null  $id  локальный database id
     */
    public function __construct(
        public readonly int $featureId,
        public readonly string $name,
        public readonly ?string $shortCode = null,
        public readonly ?int $id = null,
    ) {}
}
