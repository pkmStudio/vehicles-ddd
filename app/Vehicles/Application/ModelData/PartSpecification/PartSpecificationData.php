<?php

declare(strict_types=1);

namespace App\Vehicles\Application\ModelData\PartSpecification;

use App\Vehicles\Domain\Enums\DetailTemplateEnum;

final readonly class PartSpecificationData
{
    public function __construct(
        public string $partableType,
        public int $partableId,
        public DetailTemplateEnum $template,
        public array $details,
        public ?int $featureValueId = null,
        public ?string $name = null,
        public ?string $text = null,
    ) {}

    public function toArray(): array
    {
        return [
            'partable_type' => $this->partableType,
            'partable_id' => $this->partableId,
            'template' => $this->template,
            'feature_value_id' => $this->featureValueId,
            'name' => $this->name,
            'text' => $this->text,
            'details' => $this->details,
        ];
    }
}
