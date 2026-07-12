<?php

declare(strict_types=1);

namespace App\Templates\Domain\ModelData;

use Spatie\LaravelData\Data;

/**
 * Общий тип формы details одного из шаблонов (`WiperDetailsData`, `SparkPlugDetailsData`,
 * `OilFilterDetailsData`, `AirFilterDetailsData`) — то, что реально возвращает
 * `DetailsDataFactory::buildFromRow()`. Даёт точный доменный тип возврата вместо голого
 * `Spatie\LaravelData\Data`, которому соответствовал бы вообще любой `Data`-объект в проекте,
 * не только форма details.
 */
abstract class AbstractDetailsData extends Data {}
