<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Support;

use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use Dan\FieldTemplates\AbstractTemplate;
use Illuminate\Contracts\Container\Container;

/**
 * Резолвит класс-шаблон по DetailTemplateEnum в экземпляр через контейнер.
 * Единый источник правды о соответствии слаг → класс шаблона — сам enum
 * (DetailTemplateEnum::templateClass()); статические фабрики больше не нужны.
 */
final readonly class DetailTemplateResolver
{
    public function __construct(
        private Container $container,
    ) {}

    public function resolve(DetailTemplateEnum $template): AbstractTemplate
    {
        return $this->container->make($template->templateClass());
    }

    /**
     * @throws \ValueError если слаг не соответствует ни одному шаблону
     */
    public function resolveBySlug(string $slug): AbstractTemplate
    {
        return $this->resolve(DetailTemplateEnum::from($slug));
    }
}
