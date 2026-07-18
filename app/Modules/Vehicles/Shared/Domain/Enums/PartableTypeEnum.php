<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Enums;

/**
 * Стабильный дискриминатор полиморфной связи `part_specifications.partable_type`.
 *
 * Vehicle/Engine дублируются по фичам (у каждой фичи свой Eloquent-класс в
 * Infrastructure/Models), поэтому `::class` конкретной копии использовать нельзя — разные
 * фичи получили бы разные строки для одной и той же сущности. Значение сюда осознанно не
 * привязано ни к какому реальному PHP-классу (это не "путь", а короткое стабильное имя) —
 * подтверждено, что в БД ещё нет строк со старым значением (буквальным именем класса), поэтому
 * миграция данных при выборе этого значения не потребовалась.
 */
enum PartableTypeEnum: string
{
    case VEHICLE = 'vehicle';
    case ENGINE = 'engine';
}
