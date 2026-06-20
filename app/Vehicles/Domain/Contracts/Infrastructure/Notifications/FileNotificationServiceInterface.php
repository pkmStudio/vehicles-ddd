<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Notifications;

interface FileNotificationServiceInterface
{
    public function send(int $userId, string $csvPath, int $filesCount = 1): void;
}
