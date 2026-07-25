<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions;

use InvalidArgumentException;

/**
 * Комплект собран из несовместимых номенклатур: разные типы, бренды или категории щёток.
 */
final class KitCompositionException extends InvalidArgumentException {}
