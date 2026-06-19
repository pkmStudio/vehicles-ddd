<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\ModelData\EngineModification;

/**
 * Данные связи двигатель ↔ модификация (строка пивота engine_modification).
 */
final readonly class EngineModificationData
{
    public function __construct(
        public int $engId,
        public int $modId,
        public string $type,
    ) {}

    public function toArray(): array
    {
        return [
            'eng_id' => $this->engId,
            'mod_id' => $this->modId,
            'type' => $this->type,
        ];
    }
}
