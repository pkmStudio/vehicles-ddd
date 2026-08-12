<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Exceptions;

use DomainException;

/**
 * Ошибка неизвестного details-шаблона на public boundary Templates.
 */
final class UnknownTemplateException extends DomainException
{
    /**
     * Создает ошибку неизвестного vehicle details template.
     */
    public static function vehicle(string $template): self
    {
        return new self("Неизвестный vehicle details template: {$template}");
    }

    /**
     * Создает ошибку неизвестного nomenclature details template.
     */
    public static function nomenclature(string $template): self
    {
        return new self("Неизвестный nomenclature details template: {$template}");
    }
}
