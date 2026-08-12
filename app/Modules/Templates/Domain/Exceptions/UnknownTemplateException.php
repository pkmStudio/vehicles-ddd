<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Exceptions;

use DomainException;

/**
 * Ошибка неизвестного details-шаблона на public boundary Templates.
 */
final class UnknownTemplateException extends DomainException
{
    public static function vehicle(string $template): self
    {
        return new self("Неизвестный vehicle details template: {$template}");
    }

    public static function nomenclature(string $template): self
    {
        return new self("Неизвестный nomenclature details template: {$template}");
    }
}
