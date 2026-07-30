<?php

declare(strict_types=1);

return [
    'failures' => [
        'disk' => env('APPLICABILITY_CALCULATION_FAILURES_REPORT_DISK', 'exports'),
        'directory' => 'exports',
    ],
];
