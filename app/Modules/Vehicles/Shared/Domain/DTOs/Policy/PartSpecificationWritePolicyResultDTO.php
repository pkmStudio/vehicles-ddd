<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Policy;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Общий снимок part specification для write policy.
 */
final readonly class PartSpecificationWritePolicyResultDTO
{
    /**
     * Фиксирует результат применения правил записи part specification.
     */
    public function __construct(
        public PartableTypeEnum $partableType,
        public int $partableId,
        public DetailTemplateEnum $template,
        public array $details,
        public ?int $featureValueId = null,
        public ?string $name = null,
        public ?string $text = null,
        public ?int $id = null,
    ) {}

    /**
     * Собирает DTO из snake_case массива локального Data-снимка.
     *
     * @param  array{
     *     partable_type: string|PartableTypeEnum,
     *     partable_id: int,
     *     template: string|DetailTemplateEnum,
     *     details: array,
     *     feature_value_id?: int|null,
     *     name?: string|null,
     *     text?: string|null,
     *     id?: int|null
     * }  $payload
     */
    public static function fromArray(array $payload): self
    {
        $partableType = $payload['partable_type'];
        $template = $payload['template'];

        return new self(
            partableType: $partableType instanceof PartableTypeEnum ? $partableType : PartableTypeEnum::from($partableType),
            partableId: (int) $payload['partable_id'],
            template: $template instanceof DetailTemplateEnum ? $template : DetailTemplateEnum::from($template),
            details: $payload['details'],
            featureValueId: isset($payload['feature_value_id']) ? (int) $payload['feature_value_id'] : null,
            name: isset($payload['name']) ? (string) $payload['name'] : null,
            text: isset($payload['text']) ? (string) $payload['text'] : null,
            id: isset($payload['id']) ? (int) $payload['id'] : null,
        );
    }

    /**
     * Возвращает snake_case массив для передачи в feature-local Spatie Data.
     *
     * @return array{
     *     partable_type: string,
     *     partable_id: int,
     *     template: string,
     *     details: array,
     *     feature_value_id: int|null,
     *     name: string|null,
     *     text: string|null,
     *     id: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'partable_type' => $this->partableType->value,
            'partable_id' => $this->partableId,
            'template' => $this->template->value,
            'details' => $this->details,
            'feature_value_id' => $this->featureValueId,
            'name' => $this->name,
            'text' => $this->text,
            'id' => $this->id,
        ];
    }
}
