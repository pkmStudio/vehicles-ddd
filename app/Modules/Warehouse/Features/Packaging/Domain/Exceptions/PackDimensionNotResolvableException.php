<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Domain\Exceptions;

use RuntimeException;

/**
 * Ни одна из существующих упаковок не подошла и стратегия не создаёт новую автоматически
 * (сейчас — только `OilFilterPackagingStrategy`). Вызывающий (`KitProperties`) должен ловить именно
 * этот тип, а не общий `Throwable`, чтобы не глушить настоящие баги.
 */
final class PackDimensionNotResolvableException extends RuntimeException {}
