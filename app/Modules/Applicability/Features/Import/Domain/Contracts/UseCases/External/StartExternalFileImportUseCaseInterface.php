<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\UseCases\External;

use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;

interface StartExternalFileImportUseCaseInterface
{
    public function execute(ExternalImportFileRequestDTO $request): void;
}
