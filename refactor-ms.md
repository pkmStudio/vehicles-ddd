# План рефакторинга перед `Warehouse/MoySklad`

Цель: выровнять структуру модулей `Warehouse` и `Vehicles` перед добавлением MoySklad-фичи, убрать
межфичевые зависимости через `Catalog\Domain\Events` и зафиксировать понятную границу `Shared`.

## Статус выполнения

Выполнено:

- module-level infrastructure перенесена в `Warehouse/Shared/Infrastructure` и
  `Vehicles/Shared/Infrastructure`;
- cross-feature Catalog-события перенесены в `Shared/Domain/Events`, payload create/update
  переведён на `array`, без `Shared/Domain/ModelData`;
- добавлен публичный `TemplatesClient` и локальные adapter-порты для текущих потребителей:
  `Vehicles/Import`, `Vehicles/Export`, `Vehicles/Maintenance`, `Warehouse/Import`,
  `Warehouse/Export`;
- `Warehouse/Import`, `Warehouse/Catalog`, `Warehouse/Maintenance` переведены на локальные
  `KitPropertiesClientInterface`;
- `Warehouse/KitProperties` переведён на локальный `PackagingClientInterface`;
- провайдеры и тесты обновлены под новые биндинги.

Отложено до появления реальной потребности:

- локальные Templates-клиенты для `Warehouse/Packaging`, `Warehouse/KitProperties`,
  `Warehouse/WiperAdapterAudit`: сейчас там нет прямых factory/presenter/service вызовов Templates,
  остаются только enum-контракты шаблонов;
- `WarehouseCatalogClient`/`VehiclesCatalogClient` для будущих sync-фич, включая MoySklad.

## Целевая идея

`Warehouse` и `Vehicles` остаются модулями верхнего уровня. Внутри модуля:

- `Shared/` — публичный контракт модуля и общая инфраструктура модуля.
- `Catalog/`, `Import/`, `Export/`, `Maintenance/`, `MoySklad/` и т.п. — отдельные фичи.
- Внутри каждой фичи сохраняются слои `Domain`, `Application`, `Infrastructure`, `Presentation`.

Целевая форма:

```text
app/Warehouse/
  Shared/
    Domain/
      Events/
      Enums/
    Infrastructure/
      Database/Migrations/
      Providers/WarehouseServiceProvider.php

  Catalog/
  Import/
  Export/
  MoySklad/
  Packaging/
  KitProperties/
  WiperAdapterAudit/
  Maintenance/

app/Vehicles/
  Shared/
    Domain/
      Events/
      Enums/
    Infrastructure/
      Database/Migrations/
      Providers/VehiclesServiceProvider.php

  Catalog/
  Import/
  Export/
  Maintenance/
```

`Shared/Domain/ModelData` не заводим. `ModelData` остаются в фичах, чтобы не делать общую модель
данных скрытой точкой связанности.

## Правила для `Shared`

`Shared` — не папка для удобства, а публичный контракт модуля.

В `Shared` можно класть:

- события, которые слушают другие фичи этого же модуля;
- enum'ы, которые являются wire/db-контрактом между фичами;
- module-level инфраструктуру: миграции и провайдер, который грузит миграции.

В `Shared` не кладём:

- `ModelData`;
- Eloquent-модели;
- репозитории/commands/use cases/services;
- внутренние enum'ы конкретного workflow;
- события, которые используются только внутри одной фичи.

## Правило для enum'ов

Enum остаётся локальным для фичи, если он описывает внутреннее решение этой фичи: статус сценария,
тип операции, reason, режим импорта/экспорта и т.п.

Enum переносится в `Shared/Domain/Enums`, только если он пересекает границу фичи как контракт
данных:

- хранится в общей таблице и кастится несколькими фичами;
- приходит/уходит во внешнем payload, который обрабатывают несколько фич;
- используется несколькими фичами как единый словарь значений, где расхождение недопустимо.

Автоматически дублировать enum'ы по фичам не надо: это создаёт риск тихого рассинхрона. Но и
автоматически тащить всё в `Shared` тоже не надо: внутренние enum'ы должны оставаться локальными.

## Правило для событий

Событие лежит в `<Feature>/Domain/Events`, если это внутренний факт фичи.

Событие лежит в `<Module>/Shared/Domain/Events`, если его должны слушать другие фичи модуля.

Например:

- `Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated`
- `Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated`
- `Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted`

`Warehouse/MoySklad` должен слушать именно shared-события, а не импортировать
`Warehouse/Catalog\Domain\Events`.

Observer'ы для MoySklad не используем.

## Синхронные public clients и event-sync workflow

Если фиче нужен синхронный ответ прямо сейчас, это не event flow, а обычный service/query contract.
Такие вызовы должны идти через публичный client/API владельца, а не через внутренние сервисы,
factories, presenters или чужие `ModelData`.

Разделяем три механизма:

1. **Event** — факт "что-то произошло". Ответ не нужен, слушателей может быть много.
2. **Public client** — синхронный запрос "дай результат сейчас". Один владелец, явный return
   value, стабильные DTO/скаляры на границе.
3. **Request/result workflow** — асинхронная работа. Ответ придёт отдельным result-event по
   `runId`/`correlationId`.

Public client живёт у владельца возможности:

```text
app/Templates/
  Domain/
    Contracts/Clients/TemplatesClientInterface.php
    DTOs/Clients/...
  Application/
    Clients/TemplatesClient.php
```

Даже если владелец отдаёт публичный client, потребитель не обязан зависеть от него напрямую.
Целевой вариант для сильной изоляции: каждая фича-потребитель заводит свой локальный client-порт
на своём языке, а adapter в `Infrastructure/Clients` переводит этот порт в публичный API владельца.

```text
app/Warehouse/MoySklad/
  Domain/Contracts/Clients/WarehouseCatalogClientInterface.php
  Infrastructure/Clients/WarehouseCatalogClient.php
```

Так внутренняя Application-логика зависит от своего языка, а adapter переводит вызовы в публичный
API владельца. Это дороже по количеству файлов, но не даёт чужим DTO/enum/service-contracts
расползаться по фиче-потребителю.

Если это межфичевый workflow, то он строится как:

```text
Feature A dispatches request/fact event
Feature B listens and performs work
Feature B dispatches result event
Feature A listens to result and updates state
```

Событие не должно иметь return value. Для результата нужен отдельный result-event с
`runId`/`correlationId`.

Пример будущей формы для связи `Warehouse` и `Templates`:

```text
Warehouse -> NomenclatureDetailsNormalizationRequested
Templates -> NomenclatureDetailsNormalized
Warehouse -> applies normalized details / marks failed
```

Для таких workflow нужны:

- `runId` или `correlationId`;
- идемпотентность;
- явные состояния `pending/done/failed`, если операция должна быть надёжной;
- отдельные тесты на request/result цепочку.

На этом этапе не переводим текущие синхронные вызовы `Templates` на события. Сначала выделяем
`Shared`, переносим cross-feature события и вводим public clients там, где уже есть синхронный
межфичевый вызов.

## Где нужны public clients

### `TemplatesClient`

Сейчас `Templates` используется из `Vehicles` и `Warehouse` как набор отдельных контрактов:

- `DetailsDataFactoryInterface`;
- `NomenclatureDetailsDataFactoryInterface`;
- `DetailsDataPresenterInterface`;
- `NomenclatureDetailsDataPresenterInterface`;
- `WiperSpecificationServiceInterface`;
- `DetailTemplateEnum`;
- `NomenclatureDetailTemplateEnum`.

Это нормальная shared-kernel фича, но наружу лучше дать один публичный фасад, чтобы потребители не
знали внутреннюю разбивку Templates на factory/presenter/wiper-service. Поверх него фичи-потребители
заводят собственные локальные порты.

Целевой контракт:

```php
interface TemplatesClientInterface
{
    public function vehicleDetailHeadings(string $template): array;

    public function renderVehicleDetails(string $template, array $details): array;

    public function buildVehicleDetails(string $template, array $row, int $startIndex): array;

    public function splitVehicleWiperDetails(array $details): array;

    public function vehicleWiperSideData(array $details, string $side): array;

    public function mergeVehicleWiperForExport(array $front, array $back): array;

    public function nomenclatureDetailHeadings(string $template): array;

    public function nomenclatureReferenceOptions(string $template): array;

    public function renderNomenclatureDetails(string $template, array $details): array;

    public function buildNomenclatureDetails(string $template, array $row, int $startIndex): array;
}
```

Публичный client принимает/возвращает скаляры и arrays. Внутри он может продолжать использовать
текущие `DetailTemplateEnum`, `NomenclatureDetailTemplateEnum`, Data factory/presenter и
`WiperSpecificationService`.

Кандидаты на перевод:

- `Vehicles/Import` — сборка details из строк Excel и split/sideData дворников.
- `Vehicles/Export` — headings/render details и merge дворников для экспорта.
- `Vehicles/Maintenance` — split/merge/sideData дворников при разовых правках.
- `Vehicles/Catalog` — валидация `template` payload и cast значения шаблона.
- `Warehouse/Import` — сборка `nomenclatures.details` из строки Excel.
- `Warehouse/Export` — headings/render/reference options для `nomenclatures.details`.
- `Warehouse/Packaging` — определение поведения упаковки по шаблону номенклатуры.
- `Warehouse/KitProperties` — расчёт свойств комплектов по шаблону номенклатуры.
- `Warehouse/WiperAdapterAudit` — фильтрация/логика по шаблонам дворников/адаптеров.

Локальные порты-потребители:

```text
app/Vehicles/Import/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Infrastructure/Clients/TemplatesClient.php

app/Vehicles/Export/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Infrastructure/Clients/TemplatesClient.php

app/Vehicles/Maintenance/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Infrastructure/Clients/TemplatesClient.php

app/Warehouse/Import/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Infrastructure/Clients/TemplatesClient.php

app/Warehouse/Export/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Infrastructure/Clients/TemplatesClient.php

app/Warehouse/Packaging/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Infrastructure/Clients/TemplatesClient.php

app/Warehouse/KitProperties/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Infrastructure/Clients/TemplatesClient.php

app/Warehouse/WiperAdapterAudit/
  Domain/Contracts/Clients/TemplatesClientInterface.php
  Infrastructure/Clients/TemplatesClient.php
```

Не все локальные порты должны повторять весь `TemplatesClientInterface`. Каждый описывает только то,
что реально нужно фиче. Например `Warehouse/Packaging` может иметь метод
`resolveNomenclatureTemplate(...)`, а `Vehicles/Export` — methods для headings/render/merge дворников.

Enum'ы шаблонов можно оставить публичным контрактом `Templates` на первом шаге, если их замена на
строки сильно раздует правки. Но наружные зависимости фич на factories/presenters/wiper-service
нужно свести к локальным `Domain/Contracts/Clients/TemplatesClientInterface`.

### `WarehouseKitPropertiesClient`

Сейчас `Warehouse/Import` напрямую вызывает `Warehouse/KitProperties\Domain\Contracts\Services\
KitPropertiesServiceInterface` и вручную переводит `Import\Domain\ModelData\NomenclatureData` в
`KitProperties\Domain\ModelData\NomenclatureData`.

Это синхронный расчёт: Import должен прямо сейчас получить свойства комплекта перед записью `Kit`.
Event здесь не нужен.

Целевой контракт:

```php
interface WarehouseKitPropertiesClientInterface
{
    /**
     * @param array<int, array<string, mixed>> $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesResultDTO;
}
```

Варианты размещения:

- если считаем `KitProperties` отдельной фичей-владельцем — client живёт в
  `Warehouse/KitProperties/Domain/Contracts/Clients`;
- если хотим полностью изолировать Import — порт живёт в
  `Warehouse/Import/Domain/Contracts/Clients`, а adapter в `Warehouse/Import/Infrastructure/Clients`
  вызывает public API `KitProperties`.

Практичный первый шаг: завести public client у `KitProperties` и убрать из `Import` прямые импорты
`KitProperties\Domain\ModelData`.

### `WarehouseCatalogClient`

Для будущего `Warehouse/MoySklad` нужен синхронный доступ к Warehouse-номенклатуре и интеграционным
связям. Не стоит тащить в MoySklad внутренние repositories/DTO Catalog/Import.

Целевой подход:

- `MoySklad` слушает shared events `NomenclatureCreated/Updated/Deleted`;
- внутри job/service обращается к своему порту `WarehouseCatalogClientInterface`;
- adapter читает нужные таблицы через свою Eloquent-копию или через public client Warehouse
  Catalog, если он появится.

На первом шаге для `MoySklad` достаточно своего adapter с Eloquent-копиями, без отдельного
`WarehouseCatalogClient` для всего модуля.

### `VehiclesCatalogClient`

Пока явной новой потребности нет, но тот же паттерн понадобится, если появится фича, которой нужен
синхронный read/write ответ из `Vehicles/Catalog` без импорта его внутренних repositories/use cases.

Кандидаты на будущее:

- внешние интеграции каталога ТС;
- поиск/валидация `Vehicle`/`Engine`/`PartSpecification` из другой фичи;
- аналитика или sync-фича поверх Vehicles.

Сейчас не делать, только зафиксировать правило.

## План работ

1. Создать `app/Warehouse/Shared` и `app/Vehicles/Shared`.

2. Перенести module-level infrastructure:
   - `app/Warehouse/Infrastructure/Database/Migrations` ->
     `app/Warehouse/Shared/Infrastructure/Database/Migrations`;
   - `app/Warehouse/Infrastructure/Providers/WarehouseServiceProvider.php` ->
     `app/Warehouse/Shared/Infrastructure/Providers/WarehouseServiceProvider.php`;
   - `app/Vehicles/Infrastructure/Database/Migrations` ->
     `app/Vehicles/Shared/Infrastructure/Database/Migrations`;
   - `app/Vehicles/Infrastructure/Providers/VehiclesServiceProvider.php` ->
     `app/Vehicles/Shared/Infrastructure/Providers/VehiclesServiceProvider.php`.

3. Обновить namespaces провайдеров:
   - `App\Warehouse\Shared\Infrastructure\Providers\WarehouseServiceProvider`;
   - `App\Vehicles\Shared\Infrastructure\Providers\VehiclesServiceProvider`.

4. Обновить `bootstrap/providers.php` на новые namespaces.

5. Перенести cross-feature события Catalog в `Shared/Domain/Events`:
   - `Warehouse/Catalog/Domain/Events/{Brand,Kit,Nomenclature,PackDimension}` ->
     `Warehouse/Shared/Domain/Events/{Brand,Kit,Nomenclature,PackDimension}`;
   - `Vehicles/Catalog/Domain/Events/{Engine,Manufacturer,Modification,PartSpecification,Vehicle}` ->
     `Vehicles/Shared/Domain/Events/{Engine,Manufacturer,Modification,PartSpecification,Vehicle}`.

6. Обновить imports в Catalog use cases и tests после переноса событий.

7. Оставить внутренние import-completion events на месте:
   - `Warehouse/Import/Domain/Events/*ImportCompleted`;
   - `Vehicles/Import/Domain/Events/*ImportCompleted`.

8. Проверить enum'ы:
   - не делать массовый перенос;
   - оставить локальные workflow enum'ы в фичах;
   - отдельно выписать enum'ы, которые реально являются db/wire-контрактом нескольких фич.

9. Завести `TemplatesClientInterface` как публичный синхронный API `Templates`:
   - добавить `Domain/Contracts/Clients/TemplatesClientInterface`;
   - добавить минимальные DTO только там, где массивы становятся нечитабельными;
   - реализовать `Application/Clients/TemplatesClient` поверх текущих factories/presenters/services;
   - зарегистрировать binding в `TemplatesServiceProvider`.

10. Завести локальные ports/adapters к `Templates` в фичах-потребителях:
    - `Vehicles/Import/Domain/Contracts/Clients/TemplatesClientInterface` +
      `Vehicles/Import/Infrastructure/Clients/TemplatesClient`;
    - `Vehicles/Export/Domain/Contracts/Clients/TemplatesClientInterface` +
      `Vehicles/Export/Infrastructure/Clients/TemplatesClient`;
    - `Vehicles/Maintenance/Domain/Contracts/Clients/TemplatesClientInterface` +
      `Vehicles/Maintenance/Infrastructure/Clients/TemplatesClient`;
    - `Warehouse/Import/Domain/Contracts/Clients/TemplatesClientInterface` +
      `Warehouse/Import/Infrastructure/Clients/TemplatesClient`;
    - `Warehouse/Export/Domain/Contracts/Clients/TemplatesClientInterface` +
      `Warehouse/Export/Infrastructure/Clients/TemplatesClient`;
    - `Warehouse/Packaging/Domain/Contracts/Clients/TemplatesClientInterface` +
      `Warehouse/Packaging/Infrastructure/Clients/TemplatesClient`;
    - `Warehouse/KitProperties/Domain/Contracts/Clients/TemplatesClientInterface` +
      `Warehouse/KitProperties/Infrastructure/Clients/TemplatesClient`;
    - `Warehouse/WiperAdapterAudit/Domain/Contracts/Clients/TemplatesClientInterface` +
      `Warehouse/WiperAdapterAudit/Infrastructure/Clients/TemplatesClient`.

11. Перевести основные потребители `Templates` на свои локальные `TemplatesClientInterface`:
    - убрать прямые зависимости на `Templates\Domain\Contracts\Factories/*`;
    - убрать прямые зависимости на `Templates\Domain\Contracts\Services/*`;
    - убрать прямые зависимости на `WiperSpecificationServiceInterface`;
    - enum'ы `DetailTemplateEnum`/`NomenclatureDetailTemplateEnum` оставить как публичный контракт
      `Templates` на первом шаге, если их замена на строки слишком раздувает правки.

12. Завести adapter-порты к `Warehouse/KitProperties` в фичах-потребителях:
    - `Warehouse/Import/Domain/Contracts/Clients/KitPropertiesClientInterface` +
      `Warehouse/Import/Infrastructure/Clients/KitPropertiesClient`;
    - `Warehouse/Catalog/Domain/Contracts/Clients/KitPropertiesClientInterface` +
      `Warehouse/Catalog/Infrastructure/Clients/KitPropertiesClient`;
    - при необходимости `Warehouse/Maintenance/Domain/Contracts/Clients/KitPropertiesClientInterface`
      + `Warehouse/Maintenance/Infrastructure/Clients/KitPropertiesClient`.

13. Перевести потребителей `KitProperties` на локальные adapter-порты:
    - убрать из `Warehouse/Import` прямые импорты `KitProperties\Domain\ModelData`;
    - убрать из `Warehouse/Catalog` прямые импорты `KitProperties\Domain\ModelData`;
    - `Maintenance` можно перевести отдельным низкоприоритетным шагом;
    - оставить синхронный расчёт свойств комплекта, без событий.

14. Завести adapter-порт `PackagingClientInterface` внутри `Warehouse/KitProperties`:
    - `Warehouse/KitProperties/Domain/Contracts/Clients/PackagingClientInterface`;
    - `Warehouse/KitProperties/Infrastructure/Clients/PackagingClient`;
    - убрать из `KitProperties\Application` прямые импорты `Packaging\Domain\ModelData`;
    - оставить синхронный подбор упаковки, без событий.

15. Обновить `ARCHITECTURE.md`:
   - описать `Shared` внутри модуля;
   - убрать рекомендацию про `Shared/Domain/ModelData`;
   - зафиксировать правило enum'ов;
   - зафиксировать правило cross-feature events;
   - зафиксировать правило public clients и локальных adapter-портов для синхронных межфичевых
     запросов;
   - зафиксировать request/result event workflow для асинхронных межфичевых процессов;
   - отметить, что observer'ы для MoySklad не используются.

16. Запустить тесты:
    - сначала targeted tests по `Warehouse/Catalog` и `Vehicles/Catalog`;
    - targeted tests по `Templates`, `Warehouse/Import`, `Warehouse/Export`, `Vehicles/Import`,
      `Vehicles/Export`;
    - затем полный `php artisan test`.

## После рефакторинга

Следующим шагом можно начинать `Warehouse/MoySklad`:

- слушать `Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated`;
- слушать `Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated`;
- слушать `Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted`;
- для Excel Import выбрать batch-сценарий после `NomenclatureImportCompleted`, чтобы не создавать
  тысячи job'ов на большой файл.
