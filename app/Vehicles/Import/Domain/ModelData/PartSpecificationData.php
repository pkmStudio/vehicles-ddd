<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\ModelData;

use App\Templates\Domain\Enums\DetailTemplateEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class PartSpecificationData extends Data
{
    public function __construct(
        public readonly string $partableType,
        public readonly int $partableId,
        public readonly DetailTemplateEnum $template,
        public readonly array $details,
        public readonly ?int $featureValueId = null,
        public readonly ?string $name = null,
        public readonly ?string $text = null,
        public readonly ?int $id = null,
    ) {}
}
