<?php

declare(strict_types=1);

namespace App\Modules\Templates\Domain\Contracts;

/**
 * Контракт для enum-словарей, использующих `EnumHelperTrait` (`fromLabel`/`fromName`) — не порт
 * для DI/мокирования (enum'ы — значения, их не интерфейсим ради подмены реализации), а типовая
 * подсказка для статического анализа: код вида `$enumClass::fromLabel(...)`, где `$enumClass`
 * приходит параметром `string`, без этого контракта не может знать, что у класса вообще есть
 * такой статический метод.
 */
interface EnumHelperInterface
{
    public static function fromLabel(?string $label): ?static;

    public static function fromName(?string $name): ?static;
}
