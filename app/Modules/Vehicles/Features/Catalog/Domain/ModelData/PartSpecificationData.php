<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\ModelData;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Хранит типизированный снимок записи спецификации детали для Repository и Command.
 */
#[MapName(SnakeCaseMapper::class)]
final class PartSpecificationData extends Data
{
    /**
     * Инициализирует immutable-снимок данных спецификации детали.
     *
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $id,
        public readonly PartableTypeEnum $partableType,
        public readonly int $partableId,
        public readonly DetailTemplateEnum $template,
        public readonly array $details,
        public readonly ?int $featureValueId = null,
        public readonly ?string $name = null,
        public readonly ?string $text = null,
    ) {}
}
