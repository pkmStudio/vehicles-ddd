<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Common\Services;

use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use Dan\FieldTemplates\AbstractTemplate;

interface DetailTemplateResolverInterface
{
    public function resolve(DetailTemplateEnum $template): AbstractTemplate;

    public function resolveBySlug(string $slug): AbstractTemplate;
}

