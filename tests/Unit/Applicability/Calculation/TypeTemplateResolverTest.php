<?php

declare(strict_types=1);

namespace Tests\Unit\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Application\Services\TypeTemplateResolver;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\TypeData;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use PHPUnit\Framework\TestCase;

final class TypeTemplateResolverTest extends TestCase
{
    public function test_resolves_by_char_before_id_and_name(): void
    {
        $resolver = new TypeTemplateResolver;

        $template = $resolver->resolve(new TypeData(
            name: 'ФИЛЬТР МАСЛЯНЫЙ',
            char: 'WB',
            id: 4,
        ));

        $this->assertSame(NomenclatureDetailTemplateEnum::WIPER, $template);
    }

    public function test_resolves_by_historical_id_when_char_is_unknown(): void
    {
        $resolver = new TypeTemplateResolver;

        $template = $resolver->resolve(new TypeData(
            name: 'Неизвестный тип',
            char: 'UNKNOWN',
            id: 2,
        ));

        $this->assertSame(NomenclatureDetailTemplateEnum::SPARK_PLUGS, $template);
    }

    public function test_resolves_by_name_when_char_and_id_are_absent(): void
    {
        $resolver = new TypeTemplateResolver;

        $template = $resolver->resolve(new TypeData(
            name: ' свечи зажигания ',
        ));

        $this->assertSame(NomenclatureDetailTemplateEnum::SPARK_PLUGS, $template);
    }

    public function test_returns_null_for_unknown_type(): void
    {
        $resolver = new TypeTemplateResolver;

        $this->assertNull($resolver->resolve(new TypeData(
            name: 'Неизвестный тип',
            char: 'UNKNOWN',
            id: 999,
        )));
    }
}
