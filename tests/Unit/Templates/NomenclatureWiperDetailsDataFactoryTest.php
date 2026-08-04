<?php

declare(strict_types=1);

namespace Tests\Unit\Templates;

use App\Modules\Templates\Application\Factories\NomenclatureDetailsDataFactory;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Templates\Domain\ModelData\Nomenclature\WiperDetailsData;
use Tests\TestCase;

/**
 * Проверяет правила обязательности details-шаблона номенклатуры `wiper`.
 */
final class NomenclatureWiperDetailsDataFactoryTest extends TestCase
{
    /**
     * Проверяет, что товарная щётка может быть только передней: задняя длина и задний адаптер
     * для такой номенклатуры не обязательны.
     */
    public function test_wiper_details_allow_front_only_product(): void
    {
        $index = 0;

        $details = app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::WIPER,
            row: [
                'Переднее',
                'Бескаркасные',
                'Бескаркасная',
                'На любой сезон, Демисезон',
                600,
                450,
                null,
                'Крючок (Hook / J-Hook)',
                null,
                'Графит',
                'Нет',
                'Да',
                'Нет',
                'Нет',
                'Левый руль',
            ],
            index: $index,
        );

        self::assertInstanceOf(WiperDetailsData::class, $details);
        self::assertSame(600, $details->lengthMain);
        self::assertSame(450, $details->lengthSecond);
        self::assertNull($details->lengthRear);
        self::assertSame(['H'], $details->adapterTypeFront);
        self::assertSame([], $details->adapterTypeRear);
    }

    /**
     * Проверяет, что товарная щётка может быть только задней: передние длины и передний адаптер
     * для такой номенклатуры не обязательны.
     */
    public function test_wiper_details_allow_rear_only_product(): void
    {
        $index = 0;

        $details = app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::WIPER,
            row: [
                'Заднее',
                'Задние',
                'Каркасная',
                'На любой сезон, Демисезон',
                null,
                null,
                350,
                null,
                'RA',
                'Графит',
                'Нет',
                'Нет',
                'Нет',
                'Нет',
                'Левый руль',
            ],
            index: $index,
        );

        self::assertInstanceOf(WiperDetailsData::class, $details);
        self::assertNull($details->lengthMain);
        self::assertNull($details->lengthSecond);
        self::assertSame(350, $details->lengthRear);
        self::assertSame([], $details->adapterTypeFront);
        self::assertSame(['RA'], $details->adapterTypeRear);
    }

    public function test_wiper_details_require_driver_or_rear_length(): void
    {
        $index = 0;

        $this->expectException(DetailsDataBuildException::class);
        $this->expectExceptionMessage('Поле «Длина водительской или длина задней» обязательно для заполнения.');

        app(NomenclatureDetailsDataFactory::class)->make(
            template: NomenclatureDetailTemplateEnum::WIPER,
            row: [
                'Переднее',
                'Бескаркасные',
                'Бескаркасная',
                'На любой сезон, Демисезон',
                null,
                450,
                null,
                'Крючок (Hook / J-Hook)',
                null,
                'Графит',
                'Нет',
                'Да',
                'Нет',
                'Нет',
                'Левый руль',
            ],
            index: $index,
        );
    }
}
