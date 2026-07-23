<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Factories;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\FileImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;

interface ImportFileFactoryInterface
{
    public function make(ImportTypeEnum $type): FileImportInterface;
}
