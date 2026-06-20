<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Common;

use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use Dan\FieldTemplates\AbstractTemplate;
use Illuminate\Contracts\Container\Container;

/**
 * Резолвит класс шаблона по DetailTemplateEnum в экземпляр через контейнер.
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
