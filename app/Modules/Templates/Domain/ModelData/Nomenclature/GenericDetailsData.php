<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\ModelData\Nomenclature;

use App\Modules\Templates\Domain\ModelData\AbstractDetailsData;

/**
 * Форма шаблона `generic` (тип V_BELT — клиновой ремень). В dan-center `GenericTemplate` —
 * заглушка "для категорий, у которых ещё не настроены специфичные характеристики" — полей нет
 * вообще, `details` для этого типа всегда пустой объект. Портируется как есть.
 */
final class GenericDetailsData extends AbstractDetailsData {}
