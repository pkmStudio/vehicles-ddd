<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Engine;

use App\Vehicles\Import\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpdateEngineEditableFieldsServiceInterface;
use App\Vehicles\Import\Domain\ModelData\Engine\EngineData;

/**
 * Use-case: частичное обновление редактируемых полей двигателя по eng_id (edit-лист).
 * Какие именно колонки редактируемы — решает адаптер (привязка к раскладке Excel);
 * здесь — сама операция записи через порт Command.
 */
final readonly class UpdateEngineEditableFieldsService implements UpdateEngineEditableFieldsServiceInterface
{
    public function __construct(
        private EngineCommandInterface $command,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  колонка модели => значение
     */
    public function updateEditableFields(int $engId, array $attributes): EngineData
    {
        return $this->command->updateEditableByEngId($engId, $attributes);
    }
}
