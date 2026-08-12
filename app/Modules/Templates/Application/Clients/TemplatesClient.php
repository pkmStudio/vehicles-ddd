<?php

declare(strict_types=1);

namespace App\Modules\Templates\Application\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Templates\Domain\Contracts\Factories\DetailsDataFactoryInterface;
use App\Modules\Templates\Domain\Contracts\Factories\NomenclatureDetailsDataFactoryInterface;
use App\Modules\Templates\Domain\Contracts\Services\DetailsDataPresenterInterface;
use App\Modules\Templates\Domain\Contracts\Services\NomenclatureDetailsDataPresenterInterface;
use App\Modules\Templates\Domain\Contracts\WiperSpecificationServiceInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Templates\Domain\Exceptions\UnknownTemplateException;

/**
 * Синхронный public API Templates поверх внутренних builders/presenters/services.
 */
final readonly class TemplatesClient implements TemplatesClientInterface
{
    /**
     * Этот конструктор получает внутренние factories/presenters и сервис дворников shared-kernel.
     * Шаги:
     * 1) Сохраняет vehicle и nomenclature factories для сборки details из Excel-строк.
     * 2) Сохраняет presenters для headings/reference options/rendering.
     * 3) Сохраняет сервис wiper side/split/merge операций.
     */
    public function __construct(
        private DetailsDataFactoryInterface $vehicleFactory,
        private DetailsDataPresenterInterface $vehiclePresenter,
        private NomenclatureDetailsDataFactoryInterface $nomenclatureFactory,
        private NomenclatureDetailsDataPresenterInterface $nomenclaturePresenter,
        private WiperSpecificationServiceInterface $wiper,
    ) {}

    /**
     * Этот метод возвращает заголовки vehicle details-шаблона по строковому template key.
     * Шаги:
     * 1) Валидирует template key как `DetailTemplateEnum`.
     * 2) Делегирует получение headings vehicle presenter-у.
     */
    public function vehicleDetailHeadings(string $template): array
    {
        return $this->vehiclePresenter->headingsFor($this->vehicleTemplate($template));
    }

    /**
     * Этот метод возвращает справочники select-полей vehicle details-шаблона.
     * Шаги:
     * 1) Валидирует template key как `DetailTemplateEnum`.
     * 2) Делегирует сбор reference options vehicle presenter-у.
     */
    public function vehicleReferenceOptions(string $template): array
    {
        return $this->vehiclePresenter->referenceOptionsFor($this->vehicleTemplate($template));
    }

    /**
     * Этот метод рендерит vehicle details JSON в Excel-ячейки.
     * Шаги:
     * 1) Валидирует template key как `DetailTemplateEnum`.
     * 2) Передаёт plain details-массив в vehicle presenter.
     * 3) Возвращает плоский список ячеек.
     */
    public function renderVehicleDetails(string $template, array $details): array
    {
        return $this->vehiclePresenter->toExportCells($this->vehicleTemplate($template), $details);
    }

    /**
     * Этот метод собирает vehicle details из Excel-строки.
     * Шаги:
     * 1) Создаёт локальную копию стартового индекса, потому что public API не отдаёт курсор
     *    наружу.
     * 2) Валидирует template key и делегирует сборку vehicle factory.
     * 3) Возвращает типизированный Data-объект как plain array для внешних модулей.
     */
    public function buildVehicleDetails(string $template, array $row, int $startIndex): array
    {
        $index = $startIndex;

        return $this->vehicleFactory->make($this->vehicleTemplate($template), $row, $index)->toArray();
    }

    /**
     * Этот метод разбивает legacy vehicle wiper details на side-варианты.
     * Шаги:
     * 1) Делегирует split в `WiperSpecificationService`.
     * 2) Возвращает список вариантов с одним корневым ключом стороны.
     */
    public function splitVehicleWiperDetails(array $details): array
    {
        return $this->wiper->splitDetails($details);
    }

    /**
     * Этот метод определяет сторону сохранённых vehicle wiper details.
     * Шаги:
     * 1) Делегирует определение корневого ключа сервису дворников.
     * 2) Возвращает `front`, `back` или null для неоднозначной структуры.
     */
    public function detectVehicleWiperSide(array $details): ?string
    {
        return $this->wiper->detectSide($details);
    }

    /**
     * Этот метод разбивает сохранённую спецификацию дворников на side-варианты.
     * Шаги:
     * 1) Передаёт details и id спецификации в сервис дворников.
     * 2) Получает варианты с сохранённым `part_specification_id` для миграционного/maintenance
     *    сценария.
     */
    public function splitVehicleWiperSpecification(array $details, ?int $partSpecificationId): array
    {
        return $this->wiper->splitSpecification($details, $partSpecificationId);
    }

    /**
     * Этот метод возвращает payload одной стороны vehicle wiper details.
     * Шаги:
     * 1) Передаёт details и сторону в сервис дворников.
     * 2) Возвращает массив данных стороны или пустой массив.
     */
    public function vehicleWiperSideData(array $details, string $side): array
    {
        return $this->wiper->sideData($details, $side);
    }

    /**
     * Этот метод склеивает front/back payload дворников в legacy export-структуру.
     * Шаги:
     * 1) Передаёт раздельные стороны в сервис дворников.
     * 2) Возвращает `{front, back}` без пустых сторон.
     */
    public function mergeVehicleWiperForExport(array $front, array $back): array
    {
        return $this->wiper->mergeForExport($front, $back);
    }

    /**
     * Этот метод возвращает заголовки nomenclature details-шаблона по строковому template key.
     * Шаги:
     * 1) Валидирует template key как `NomenclatureDetailTemplateEnum`.
     * 2) Делегирует получение headings nomenclature presenter-у.
     */
    public function nomenclatureDetailHeadings(string $template): array
    {
        return $this->nomenclaturePresenter->headingsFor($this->nomenclatureTemplate($template));
    }

    /**
     * Этот метод возвращает справочники select-полей nomenclature details-шаблона.
     * Шаги:
     * 1) Валидирует template key как `NomenclatureDetailTemplateEnum`.
     * 2) Делегирует сбор reference options nomenclature presenter-у.
     */
    public function nomenclatureReferenceOptions(string $template): array
    {
        return $this->nomenclaturePresenter->referenceOptionsFor($this->nomenclatureTemplate($template));
    }

    /**
     * Этот метод рендерит nomenclature details JSON в Excel-ячейки.
     * Шаги:
     * 1) Валидирует template key как `NomenclatureDetailTemplateEnum`.
     * 2) Передаёт plain details-массив в nomenclature presenter.
     * 3) Возвращает плоский список ячеек.
     */
    public function renderNomenclatureDetails(string $template, array $details): array
    {
        return $this->nomenclaturePresenter->toExportCells($this->nomenclatureTemplate($template), $details);
    }

    /**
     * Этот метод собирает nomenclature details из Excel-строки.
     * Шаги:
     * 1) Создаёт локальную копию стартового индекса.
     * 2) Валидирует template key и делегирует сборку nomenclature factory.
     * 3) Возвращает Data-объект как plain array для внешнего кода.
     */
    public function buildNomenclatureDetails(string $template, array $row, int $startIndex): array
    {
        $index = $startIndex;

        return $this->nomenclatureFactory->make($this->nomenclatureTemplate($template), $row, $index)->toArray();
    }

    /**
     * Этот метод валидирует строковый key vehicle-шаблона.
     * Шаги:
     * 1) Пробует собрать `DetailTemplateEnum` из строки.
     * 2) Если key неизвестен — бросает `UnknownTemplateException::vehicle()`.
     */
    private function vehicleTemplate(string $template): DetailTemplateEnum
    {
        return DetailTemplateEnum::tryFrom($template) ?? throw UnknownTemplateException::vehicle($template);
    }

    /**
     * Этот метод валидирует строковый key nomenclature-шаблона.
     * Шаги:
     * 1) Пробует собрать `NomenclatureDetailTemplateEnum` из строки.
     * 2) Если key неизвестен — бросает `UnknownTemplateException::nomenclature()`.
     */
    private function nomenclatureTemplate(string $template): NomenclatureDetailTemplateEnum
    {
        return NomenclatureDetailTemplateEnum::tryFrom($template)
            ?? throw UnknownTemplateException::nomenclature($template);
    }
}
