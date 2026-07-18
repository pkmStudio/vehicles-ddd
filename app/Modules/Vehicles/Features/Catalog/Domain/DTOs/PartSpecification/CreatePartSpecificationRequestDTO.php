<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\PartSpecification;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;

/**
 * Передает параметры создания PartSpecification из внешнего сообщения.
 */
final readonly class CreatePartSpecificationRequestDTO
{
    /**
     * Инициализирует immutable-снимок запроса создания спеки.
     *
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $id,
        public PartSpecificationOwnerDTO $owner,
        public DetailTemplateEnum $template,
        public array $details,
        public ?int $featureValueId = null,
        public ?string $name = null,
        public ?string $text = null,
    ) {}
}
