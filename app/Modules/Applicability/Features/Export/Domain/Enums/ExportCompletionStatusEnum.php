<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Enums;

enum ExportCompletionStatusEnum: string
{
    case Completed = 'completed';
    case Failed = 'failed';
}
