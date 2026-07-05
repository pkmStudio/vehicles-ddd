<?php

declare(strict_types=1);

namespace App\Vehicles\Templates\Domain\Contracts;

use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
use Dan\FieldTemplates\AbstractTemplate;

interface DetailTemplateResolverInterface
{
    public function resolve(DetailTemplateEnum $template): AbstractTemplate;

    public function resolveBySlug(string $slug): AbstractTemplate;
}

