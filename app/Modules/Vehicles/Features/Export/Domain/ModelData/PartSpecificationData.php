<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\ModelData;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class PartSpecificationData extends Data
{
    /**
     * @param  string  $partableType  polymorphic owner type из общего Vehicles discriminator
     * @param  int  $partableId  идентификатор владельца specification
     * @param  DetailTemplateEnum  $template  typed details-шаблон specification
     * @param  array<string, mixed>  $details  сохраненный payload details
     * @param  int|null  $featureValueId  ссылка на feature value для specification
     * @param  string|null  $name  пользовательское имя specification
     * @param  string|null  $text  произвольный текст specification
     * @param  int|null  $id  локальный database id
     * @param  FeatureValueData|null  $featureValue  eager-loaded значение фичи для export rows
     */
    public function __construct(
        public readonly string $partableType,
        public readonly int $partableId,
        public readonly DetailTemplateEnum $template,
        public readonly array $details,
        public readonly ?int $featureValueId = null,
        public readonly ?string $name = null,
        public readonly ?string $text = null,
        public readonly ?int $id = null,
        /** заполняется только когда явно eager-loaded (лист дворников) */
        public readonly ?FeatureValueData $featureValue = null,
    ) {}
}
