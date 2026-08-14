<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Events;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

final readonly class PartSpecificationEventPayloadDTO
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public int $id,
        public PartableTypeEnum $partableType,
        public int $partableId,
        public DetailTemplateEnum $template,
        public array $details,
        public ?int $featureValueId = null,
        public ?string $name = null,
        public ?string $text = null,
    ) {}
}
