<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\UseCases\Engine;

use App\Vehicles\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Domain\Models\Engine;

/**
 * Use-case: частичное обновление редактируемых полей двигателя по eng_id (edit-лист).
 * Какие именно колонки редактируемы — решает адаптер (привязка к раскладке Excel);
 * здесь — сама операция записи через порт Command.
 */
final readonly class UpdateEngineEditableFieldsUseCase
{
    public function __construct(
        private EngineCommandInterface $command,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  поле модели => значение
     */
    public function execute(int $engId, array $attributes): Engine
    {
        return $this->command->updateEditableByEngId($engId, $attributes);
    }
}
