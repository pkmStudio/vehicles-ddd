<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\ModelData;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ManufacturerData extends Data
{
    /**
     * @param  int  $mfaId  внешний TecDoc manufacturer id
     * @param  string  $name  название производителя для export row
     * @param  ProviderEnum  $provider  источник записи каталога
     * @param  int|null  $id  локальный database id
     */
    public function __construct(
        public readonly int $mfaId,
        public readonly string $name,
        public readonly ProviderEnum $provider,
        public readonly ?int $id = null,
    ) {}
}
