<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Exceptions;

use DomainException;

/**
 * Ошибка валидации одной строки импорта применяемости.
 */
final class ImportRowValidationException extends DomainException {}
