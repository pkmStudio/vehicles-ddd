<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Presentation\Http\Controllers;

use App\Modules\Applicability\Features\Catalog\Application\UseCases\CheckNomenclatureApplicabilityUseCase;
use App\Modules\Applicability\Features\Catalog\Application\UseCases\ListApplicableCategoriesUseCase;
use App\Modules\Applicability\Features\Catalog\Application\UseCases\ListApplicableNomenclaturesUseCase;
use App\Modules\Applicability\Features\Catalog\Domain\Enums\ApplicabilityLookupStatusEnum;
use App\Modules\Applicability\Features\Catalog\Presentation\Http\Presenters\CatalogApplicabilityPresenter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP adapter применяемости комплектов для публичного каталога dan-catalog.
 */
final readonly class CatalogApplicabilityController
{
    private const int DAN_BRAND_ID = 3;

    public function __construct(
        private CheckNomenclatureApplicabilityUseCase $checkApplicability,
        private ListApplicableCategoriesUseCase $listCategories,
        private ListApplicableNomenclaturesUseCase $listNomenclatures,
        private CatalogApplicabilityPresenter $presenter,
    ) {}

    /** Проверяет положительную применяемость артикула для modification_id. */
    public function check(Request $request, string $partNumber): Response
    {
        $partNumber = trim($partNumber);
        $modificationId = $this->positiveIntegerQuery($request, 'modification_id');
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
            ApplicabilityLookupStatusEnum::COMPATIBLE => response()->json(['data' => $this->presenter->check($result)]),
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

        return response()->json(['data' => $this->presenter->categories($categories)]);
    }

    /** Возвращает страницу применимых товаров выбранной категории. */
    public function nomenclatures(Request $request, int $modification, int $category): Response
    {
        $page = $this->positiveIntegerQuery($request, 'page', 1);
        $pageSize = $this->positiveIntegerQuery($request, 'page_size', 9);
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

        return response()->json(['data' => $this->presenter->page($result)]);
    }

    /** Возвращает DAN brand id по умолчанию или строгий положительный brand_id. */
    private function brandId(Request $request): ?int
    {
        if (! $request->query->has('brand_id')) {
            return self::DAN_BRAND_ID;
        }

        return $this->positiveIntegerQuery($request, 'brand_id');
    }

    /** Читает query-параметр и отсекает не-скалярный внешний payload на HTTP boundary. */
    private function positiveIntegerQuery(Request $request, string $key, ?int $default = null): ?int
    {
        $value = $request->query($key, $default);

        if (! is_int($value) && ! is_string($value) && $value !== null) {
            return null;
        }

        return $this->positiveInteger($value);
    }

    /** Валидирует значение как положительный integer без неявного coercion. */
    private function positiveInteger(int|string|null $value): ?int
    {
        $integer = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        return is_int($integer) ? $integer : null;
    }
}
