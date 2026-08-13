<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases\CheckNomenclatureApplicabilityUseCaseInterface;
use App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases\ListApplicableCategoriesUseCaseInterface;
use App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases\ListApplicableNomenclaturesUseCaseInterface;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableCategoryDTO;
use App\Modules\Applicability\Features\Catalog\Domain\Enums\ApplicabilityLookupStatusEnum;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter применяемости комплектов для публичного каталога dan-catalog.
 */
final readonly class CatalogApplicabilityController
{
    private const int DAN_BRAND_ID = 3;

    public function __construct(
        private CheckNomenclatureApplicabilityUseCaseInterface $checkApplicability,
        private ListApplicableCategoriesUseCaseInterface $listCategories,
        private ListApplicableNomenclaturesUseCaseInterface $listNomenclatures,
    ) {}

    /** Проверяет положительную применяемость артикула для modification_id. */
    public function check(Request $request, string $partNumber): Response
    {
        $partNumber = trim($partNumber);
        $modificationId = $this->positiveInteger($request->query('modification_id'));
        $brandId = $this->brandId($request);

        if ($partNumber === '' || $modificationId === null) {
            return response()->json(['message' => 'Invalid applicability parameters.'], Response::HTTP_BAD_REQUEST);
        }

        if ($brandId === null) {
            return response()->json(['message' => 'Invalid brand parameter.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->checkApplicability->execute(
            partNumber: $partNumber,
            modificationId: $modificationId,
            brandId: $brandId,
        );

        return match ($result->status) {
            ApplicabilityLookupStatusEnum::COMPATIBLE => response()->json(['data' => $result->toArray()]),
            ApplicabilityLookupStatusEnum::UNKNOWN => response()->noContent(),
            ApplicabilityLookupStatusEnum::NOMENCLATURE_NOT_FOUND => response()->json(
                ['message' => 'Nomenclature not found.'],
                Response::HTTP_NOT_FOUND,
            ),
            ApplicabilityLookupStatusEnum::MODIFICATION_NOT_FOUND => response()->json(
                ['message' => 'Modification not found.'],
                Response::HTTP_NOT_FOUND,
            ),
        };
    }

    /** Возвращает категории с применимыми товарами выбранного бренда. */
    public function categories(Request $request, int $modification): Response
    {
        $brandId = $this->brandId($request);

        if ($brandId === null) {
            return response()->json(['message' => 'Invalid brand parameter.'], Response::HTTP_BAD_REQUEST);
        }

        $categories = $this->listCategories->execute(
            modificationId: $modification,
            brandId: $brandId,
        );

        if ($categories === null) {
            return response()->json(['message' => 'Modification not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $categories
                ->map(static fn (ApplicableCategoryDTO $category): array => $category->toArray())
                ->values()
                ->all(),
        ]);
    }

    /** Возвращает страницу применимых товаров выбранной категории. */
    public function nomenclatures(Request $request, int $modification, int $category): Response
    {
        $page = $this->positiveInteger($request->query('page', 1));
        $pageSize = $this->positiveInteger($request->query('page_size', 9));
        $brandId = $this->brandId($request);

        if ($page === null || $pageSize === null || $pageSize > 100) {
            return response()->json(['message' => 'Invalid pagination parameters.'], Response::HTTP_BAD_REQUEST);
        }

        if ($brandId === null) {
            return response()->json(['message' => 'Invalid brand parameter.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->listNomenclatures->execute(
            modificationId: $modification,
            categoryId: $category,
            brandId: $brandId,
            page: $page,
            pageSize: $pageSize,
        );

        if ($result === null) {
            return response()->json(['message' => 'Modification or category not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $result->toArray()]);
    }

    /** Возвращает DAN brand id по умолчанию или строгий положительный brand_id. */
    private function brandId(Request $request): ?int
    {
        if (! $request->query->has('brand_id')) {
            return self::DAN_BRAND_ID;
        }

        return $this->positiveInteger($request->query('brand_id'));
    }

    /** Валидирует значение как положительный integer без неявного coercion. */
    private function positiveInteger(mixed $value): ?int
    {
        $integer = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        return is_int($integer) ? $integer : null;
    }
}
