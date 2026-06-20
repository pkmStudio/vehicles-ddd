<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Export\Support;

use Illuminate\Database\Eloquent\Model;

interface ExportDetailsBuilderInterface
{
    public function extractHeadingsFromTemplate(array $template): array;

    public function getDetailsData(Model $model, array $template): array;
}

