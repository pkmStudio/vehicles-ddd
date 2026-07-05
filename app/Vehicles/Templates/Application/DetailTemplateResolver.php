<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Application;

use App\Vehicles\Templates\Domain\Contracts\DetailTemplateResolverInterface;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Dan\FieldTemplates\AbstractTemplate;

/**
 * Резолвит класс шаблона по DetailTemplateEnum в экземпляр.
 * Шаблоны — чистые декларации без конструкторных зависимостей, поэтому создаются через `new`
 * (контейнер не нужен — резолвер остаётся чистым Application-классом, без инфраструктуры).
 */
final readonly class DetailTemplateResolver implements DetailTemplateResolverInterface
{
    public function resolve(DetailTemplateEnum $template): AbstractTemplate
    {
        $class = $template->templateClass();

        return new $class;
    }

    /**
     * @throws \ValueError если слаг не соответствует ни одному шаблону
     */
    public function resolveBySlug(string $slug): AbstractTemplate
    {
        return $this->resolve(DetailTemplateEnum::from($slug));
    }
}
