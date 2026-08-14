# Architecture: модульный монолит

## Обзор

`dan-vehicles` использует модульный монолит: один Laravel deployment с жесткими границами между доменными модулями и фичами. Это соответствует фактической структуре `app/Modules/*`, текущим интеграциям через RabbitMQ/S3/MoySklad и уровню доменной сложности импортов, экспортов, каталога, применяемости и шаблонов `details`.

Внутри фич применяется Clean Architecture dependency rule: зависимости направлены внутрь, к `Domain`. `Application` оркестрирует сценарии через domain ports, `Infrastructure` реализует IO и framework adapters, `Presentation` остается тонким входным слоем.

Проект не стремится к framework-agnostic core: `dan-vehicles` остаётся Laravel-сервисом. Поэтому
часть Laravel-типов считается осознанным компромиссом, а не нарушением архитектуры: Support
Collection в read-портах/Application, Laravel Validator/`Rule::enum(...)` в row factories,
cache/events contracts в orchestration/gate-сценариях. При этом Eloquent, Excel, RabbitMQ, S3,
filesystem adapters и прямые Laravel facades записи/логирования в обычном Application-коде должны
оставаться за портами или в Infrastructure.

DDD применяется стратегически: bounded contexts, explicit contracts, events, ubiquitous language и
context map. Tactical DDD с жирными entities/aggregates не является целью по умолчанию. `ModelData`
намеренно остаются тонкими снимками состояния, Eloquent-модели — анемичными Infrastructure-деталями,
а бизнес-сценарии и правила живут в Application Services/UseCases.

## Обоснование решения

- **Тип проекта:** backend-сервис каталога и интеграционных workflow с насыщенными бизнес-правилами.
- **Технологический стек:** PHP 8.4, Laravel 13, PostgreSQL 17, Eloquent, Horizon, RabbitMQ, `maatwebsite/excel`, `spatie/laravel-data`.
- **Ключевой фактор:** в проекте уже есть bounded contexts (`Vehicles`, `Warehouse`, `Applicability`, `Templates`) и feature-first раскладка, но независимый deployment микросервисов сейчас дал бы лишнюю операционную сложность.

## Структура папок

```text
app/Modules/
  Vehicles/
    Shared/
      Domain/
        Contracts/Clients/  # Публичные client-контракты модуля
        DTOs/               # Публичные DTO межмодульных вызовов
        Enums/              # Общие wire/db enum-контракты модуля
        Events/             # Публичные факты для фич модуля
      Infrastructure/
        Clients/            # Адаптеры к другим модулям
        Database/           # Module-level migrations
        Providers/          # Module-level service provider
    Features/
      Import/
      Export/
      Catalog/
      Maintenance/
  Warehouse/
    Shared/
    Features/
      Import/
      Export/
      Catalog/
      Packaging/
      KitProperties/
      MoySklad/
      Maintenance/
      WiperAdapterAudit/
  Applicability/
    Shared/
    Features/
      Import/
      Export/
      Calculation/
  Templates/
    Domain/                 # Typed declarations для details-шаблонов
    Application/            # Сборка из строк, render/presenter, template services
    Infrastructure/         # Provider bindings
```

Типовая фича:

```text
app/Modules/<Module>/Features/<Feature>/
  Domain/
    Contracts/
      Commands/             # Write ports
      Repositories/         # Read ports
      Services/             # Application service ports
      Factories/            # Factory/selector ports
      Imports/              # Import ports, если фича читает внешние файлы
      Exports/              # Export ports, если фича пишет Excel
      Notifications/        # Outbound notification ports
    DTOs/                   # Scenario payloads/results/context
    ModelData/              # spatie Data snapshots
    Enums/                  # Feature-local enums
    Events/                 # Domain facts
    Exceptions/             # Domain exceptions
  Application/
    UseCases/               # Точки входа сценариев
    Services/               # Правила и orchestration
    Factories/              # Row mapping and Data builders; adapter selectors live in Infrastructure
    Listeners/              # Тонкие реакции на domain events
  Infrastructure/
    Models/                 # Feature-local Eloquent models
    Repositories/           # Read adapters
    Commands/               # Write adapters
    Imports/                # Excel import adapters
    Exports/                # Excel export adapters
    Factories/              # Import/export adapter selectors
    Messaging/              # RabbitMQ handlers/validators
    Notifications/          # RabbitMQ/S3/other outbound adapters
    Providers/              # DI/event bindings
  Presentation/
    Console/Commands/       # Artisan commands
    Http/Controllers/       # HTTP entrypoints, если нужны
```

## Правила зависимостей

- `Domain` не зависит от Laravel facades, Eloquent, Excel, RabbitMQ, S3, cache или filesystem adapters.
- `Domain` может использовать `Illuminate\Support\Collection` как локально принятый тип read-портов,
  если это Support Collection, а не Eloquent Collection.
- `Application` зависит от `Domain` своей фичи и от явно разрешенных domain contracts shared-kernel модулей.
- `Application` может использовать Laravel Validator/Rule в row factories и Laravel contracts для
  cache/events orchestration, но не должен напрямую работать с Eloquent, Excel, RabbitMQ, S3 или
  filesystem adapters.
- `Infrastructure` реализует ports из `Domain/Contracts` и содержит Eloquent, Excel, RabbitMQ, S3, cache, filesystem и framework code.
- Выбор конкретных import/export Excel adapters по enum/типу запроса находится в
  `Infrastructure/Factories` или provider closures за domain-портом; Application/use case не
  владеет adapter-specific constructor parameters.
- `Presentation` парсит вход, валидирует entrypoint-level параметры и вызывает concrete use case
  или service port.
- Межфичевый sync-вызов идет через локальный `Domain/Contracts/Clients/*ClientInterface` фичи-потребителя и adapter в ее `Infrastructure/Clients`.
- События используются только для фактов без return value; если нужен ответ сразу, это client/query contract, не event.
- `Shared` внутри доменного модуля — публичная часть модуля, а не папка для общей бизнес-логики.
- Верхнеуровневый `app/Modules/Shared` не используется: технические workflow, console base classes,
  Excel helpers и adapters размещаются внутри конкретной фичи. Небольшое дублирование между
  модулями допустимо ради изоляции bounded contexts.

## Коммуникация слоев и модулей

- Cross-feature факт внутри модуля лежит в `<Module>/Shared/Domain/Events`.
- Внутренний факт фичи лежит в `<Feature>/Domain/Events`.
- Межсервисный request/result workflow оформляется двумя сообщениями: request/fact event и result
  event с `operation_id`. Новые import/export/calculation workflows используют `operation_id` для
  cache/report state, идемпотентности и уведомлений; `runId` допустим только как legacy/internal
  runtime key там, где он реально еще остался.
- RabbitMQ inbound: `Infrastructure/Messaging/Handlers/*Handler` валидирует payload через `Messaging/Validators`, собирает DTO и вызывает use case.
- RabbitMQ outbound: `Infrastructure/Notifications/*NotificationService` публикует explicit notification DTO.
- RabbitMQ config заполняется через enum-контракты `dan-wire-contracts`, а outbound result payload
  соответствует wire DTO пакета. `dan-vehicles` публикует machine-readable status/counters/report
  path/errors; человекочитаемый текст уведомления формирует consumer (`dan-center`).
- Расчет применяемости `APPLICABILITY_CALCULATION_REQUESTED` выполняется через chunked queue flow:
  handler ставит dispatcher job, dispatcher читает Warehouse kits через read client и режет ids на
  chunk jobs, chunk jobs вызывают существующий calculation use case без промежуточных внешних
  result events, finalizer публикует один `APPLICABILITY_CALCULATION_COMPLETED`.
- Runtime-state chunked расчета (`chunks`, counters, affected ids, errors, finalization/idempotency
  markers) хранится в Laravel Cache/Redis через infrastructure service и очищается finalizer-ом.
  Таблицы runs/chunks для этого state не заводим; ошибки остаются в failure report notification и
  `warning`/`error` логах.
- Excel import: adapter в `Infrastructure/Imports` читает файл, mapper собирает typed row DTO из
  сырого row array и явно проставляет контекст источника (`provider`, `allow_change_fields`,
  `operation_id`, generated IDs, если они являются частью формата). Application service не принимает
  raw row arrays и не подставляет provider defaults; он делегирует в Application factory.
- Индексы Excel/CSV колонок в mapper/import parser объявляются именованными `private const int`
  (`MFA_ID = 0`, `NAME = 1` и т.п.) и используются через `$row[self::...]`; голые `$row[0]` /
  `$row[1]` в import parsing не допускаются.
- Import mappers валидируют жесткий контракт строки: обязательные колонки и enum/source markers
  должны быть заданы явно. Если значение не лежит в файле, но известно из adapter-а источника
  (например TecDoc provider), mapper проставляет его явно, а не через default в `Data`.
- Application factory может иметь несколько публичных методов, если у сущности несколько typed row
  DTO/источников (`makeFromTdRow(...)`, `makeFromSheetRow(...)`). Каждый метод принимает конкретный
  DTO и валидирует его формат; factory не принимает raw row array и не выбирает сценарий по
  строковому `mode`.
- Queued Excel import: import adapter не хранит service/repository/client/logger dependency graph в
  свойствах. Для классов `ShouldQueue` зависимости резолвятся в `collection()` или другом worker-time
  методе, а `registerEvents()` не использует closures.
- Excel export: adapter в `Infrastructure/Exports` читает данные через Repository и собирает строки через Application services/factories.
- Maintenance-фичи могут ходить в Eloquent напрямую из своих services/commands, если это осознанный разовый catalog fix без reusable Repository boundary.
- Public sync clients между фичами/модулями только читают данные. Запись выполняется через use
  case/command или async event/result workflow, не через `*ClientInterface`.
- Public sync clients не читают БД напрямую: они вызывают owner use case/query service или
  repository port, SQL остаётся в `Infrastructure/Repositories`.
- Public shared events несут scalar fields или typed event payload DTO/value objects, не raw
  `array` payload'ы сущностей/интеграций.
- CRUD shared events используют entity-specific `<Entity>EventPayloadDTO`, не универсальный
  `CatalogEventPayloadDTO`/raw snapshot. Payload собирается явно в отдельную переменную перед
  `event(new ...)`; не используем `fromData(object)`/`fromModel(object)` без точного входного типа.
- REST CRM read API — read-only boundary для `dan-center`: entity-specific controllers/routes,
  concrete read use cases/query services, presenters/response DTO for HTTP shape, service-key
  middleware. Запись каталога через REST CRM read API не делаем.

## Context Map

Физические модули `app/Modules/*` считаются bounded contexts. Отношения между ними фиксируются
явно, чтобы не возникало скрытых зависимостей на чужие Application/Infrastructure классы.

| Context | Чем владеет | Публичная поверхность | Зависимости / потребители |
|---|---|---|---|
| `Vehicles` | Каталог автомобилей: `Vehicle`, `Engine`, `Manufacturer`, `Modification`, vehicle/engine `PartSpecification`. | Shared events created/updated/deleted, client contracts/DTOs для чтения данных Vehicles. | Потребители: `Applicability`, частично `Warehouse`/export-сценарии. Не отдаёт Eloquent-модели наружу. |
| `Warehouse` | Складской каталог: `Nomenclature`, `Kit`, `Type`, `PackDimension`, свойства наборов, аудит дворников, интеграция MoySklad. | Shared events, client contracts/DTOs для применяемости и export/audit-сценариев. | Потребители: `Applicability`, `Templates`, внешние интеграции. `MoySklad` остаётся под-контекстом Warehouse и не является общим API. |
| `Applicability` | Импорт, экспорт и расчёт применяемости комплектов к автомобилям. | Сценарии расчёта/экспорта, события завершения, notification DTOs. | Потребляет публичные contracts `Vehicles` и `Warehouse`; не владеет каталогами Vehicles/Warehouse. |
| `Templates` | Shared Kernel формы `details`: enum-словари, typed `Data`, сборка из строк и рендер в Excel. | Domain declarations + service/factory contracts, используемые другими context'ами. | Используется Vehicles/Warehouse/Applicability. Любое изменение формы `details` считается cross-context изменением. |

Правило context map: если context'у нужен ответ прямо сейчас, он использует sync client contract и
adapter. Если нужно сообщить факт без ответа — domain event. Прямой импорт чужих
`Application\Services`, `Application\Factories`, presenters, Eloquent-моделей и feature-local
`ModelData` запрещён.

## Правила слоев

### Domain

- Хранит ports, DTO/Data, enums, events и domain exceptions.
- DTO могут иметь простой `toArray()`/`fromArray()` для механической сериализации собственного
  состояния. HTTP/RabbitMQ defaults, validation, config lookup, Eloquent/paginator/external payload
  mapping и сборка из нескольких объектов остаются в Presentation/Infrastructure factory,
  presenter или adapter.
- `ModelData` — снимки строк/сущностей через `spatie/laravel-data`; enum-поля типизируются реальными enum-классами.
- `ModelData` не содержит import/export/create/update методов и не превращается в rich entity.
  Бизнес-сценарии остаются в Application.
- `ModelData` не подставляет ownership/source defaults. Если `provider` обязателен для записи, он
  обязателен в constructor. Для `engines.provider` и `modifications.provider` database default не
  используется: `TD`/`OD` явно задает import/catalog adapter.
- Provider ownership conflict не исправляется автоматически: сценарий должен завершить строку
  import validation/domain ошибкой, чтобы источник данных не менялся молча.
- Events — `final readonly` факты в прошедшем времени, без поведения и без суффикса `Event`, если это соответствует существующему неймингу.
- Общие enum-словарь и wire/db contracts кладутся в module-level `Shared/Domain/Enums`, а workflow-local enum остается в фиче.

### Application

- Оркестрирует сценарии и прикладные правила.
- `UseCases` — concrete Application entrypoints без интерфейсов в `Domain/Contracts`; Presentation,
  handlers и listeners могут инжектить конкретный use case класс.
- Интерфейсы в `Domain/Contracts` оставляем для ports: repositories, commands, services, factories,
  imports/exports, notifications, public clients и других boundary-зависимостей.
- Listeners остаются в `Application`, не в `Infrastructure`, если это in-process реакция на domain event.
- In-process domain facts отправляем через helper `event(new DomainFact(...))`; dispatcher contract
  инжектим только когда это реально boundary/subscribe-сценарий.
- Application не использует `Model::query()`, `updateOrCreate()`, `save()` и другие Eloquent operations, кроме явно выделенных Maintenance-исключений.

### Infrastructure

- Eloquent-модели feature-local и анемичные: relations, casts, timestamps, без бизнес-логики.
- Repository — только чтение, возвращает `Data`, `Collection<int, Data>`, `Generator<int, Data>`
  или узкий scalar read вроде `exists`, `count`, `nextId`, если это атомарное чтение без
  бизнес-логики и записи.
- Command — запись, принимает `Data`/DTO и инкапсулирует save/upsert/delete.
- Command methods называем по действию (`create(Data)`, `update(Data)`, `delete(Data)`), если ключ
  уже лежит в `Data`; `updateByX/deleteByX` допустимы только при отдельном scalar key-аргументе.
- Messaging, Files, Cache, Storage, Jobs, Notifications и external clients остаются здесь.
- Cache gate/service инкапсулирует Laravel Cache/Redis доступ. Если cache flag/key является частью
  observable контракта gate-сервиса, константа живет на port/interface, не private const в adapter.
- Laravel Excel adapters с `ShouldQueue` должны быть сериализуемыми: свойства — только scalar/DTO/value
  state; application services, repositories, clients и loggers резолвятся во время выполнения job.
- Queued Excel imports не принимают service/repository/mapper через constructor; используют
  worker-time lazy getters (`service()`, `rowMapper()`), а `__serialize()`/`__unserialize()` нужны
  только для реального scalar/DTO state (`context`, `cacheKey`, `lockKey`).
- Event listeners для queued imports регистрируются как сериализуемые callables, например
  `[self::class, 'afterImport']`, а не closure.
- Production `info`/`debug` logs не используются для нормального успешного потока; оставляем
  `warning`/`error` только для actionable интеграционных аномалий и сбоев. `$this->info()` в
  Artisan commands считается console output.

### Presentation

- Artisan commands и HTTP controllers должны быть тонкими.
- Entry point не содержит доменной логики и не работает напрямую с Eloquent.
- Регистрация команд идет через Laravel bootstrap/providers, а реализация сценария остается в Application.

### Templates Shared Kernel

- `Templates` — единственный полноценный shared-kernel проекта для формы `details`.
- Публичная поверхность: `Domain/ModelData`, `Domain/Enums`, `Domain/Contracts` и Application
  реализации только через contracts.
- Изменение структуры `details`, порядка import/export колонок или enum-значений считается
  cross-context change.
- Перед изменением `Templates` проверяются сценарии `Vehicles Import`, `Vehicles Export`,
  `Warehouse Import/Export`, `Applicability`.
- `Templates` не должен зависеть от `Vehicles`, `Warehouse` или `Applicability`.

## Глоссарий домена

| Термин | Значение |
|---|---|
| `Vehicle` | Модель/поколение автомобиля в каталоге Vehicles. |
| `Engine` | Двигатель и связанные характеристики/группы двигателя. |
| `Modification` | Модификация автомобиля, связывающая модель, тип и двигатель. |
| `PartSpecification` | Спецификация детали для автомобиля/двигателя с полиморфной формой `details`. |
| `Nomenclature` | Складская товарная позиция. |
| `Kit` | Набор складских номенклатур. |
| `Type` | Тип складской номенклатуры/набора. |
| `Applicability` | Применяемость складского комплекта к автомобилю/модификации. |
| `Template` / `details` | Типизированная форма характеристик детали, общая для import/export. |
| `MoySklad` | Внешняя складская система; внутри проекта это под-контекст Warehouse-интеграции. |

## Ключевые принципы

1. Сначала выбирайте module и feature, потом слой.
2. Граница фичи важнее устранения всей дубликации: feature-local Eloquent-модели допустимы.
3. Read/write разделены CQRS-lite стилем: Repository читает, Command пишет.
4. `Templates` — shared-kernel для формы `details`: декларации в Domain, сборка/рендер в Application.
5. Интеграционная невалидность не должна бесконечно ретраиться: invalid broker payload логируется и отбрасывается.
6. Любое новое внешнее взаимодействие оформляется adapter-ом в Infrastructure и port-ом в Domain, если его вызывает Application.

## Известные архитектурные долги

К исправлению в ближайших итерациях:

- Докблоки классов/методов, inline `new DTO(...)` в вызовах и многострочные именованные аргументы
  приводятся к правилам постепенно, без больших шумных форматирующих PR.
- Для новых queued Excel imports нужны обязательные serialization regression tests: `serialize($import)`
  и сериализация каждого listener из `registerEvents()`.

## Тестовая стратегия

- Feature-тесты покрывают бизнес-сценарии через реальные boundaries: handlers/use cases/DB/Excel,
  files/cache, create/update/delete/reject/idempotency/cascade/export-row outcomes.
- Unit-тесты оставляем для чистых правил, deterministic algorithms, validation/mapping edge cases и
  narrow architecture regressions вроде queued import serialization.
- Пустые framework examples и brittle tests, которые проверяют только порядок mock-вызовов
  repositories/commands без бизнес-исхода, удаляются или заменяются feature/domain-rule coverage.

## Куда класть новое

| Что добавляется | Куда класть |
|---|---|
| Новый сценарий с внешним триггером | `<Feature>/Application/UseCases/<Group>/` concrete class; без `Domain/Contracts/UseCases` |
| Новое прикладное правило | `<Feature>/Application/Services/<Entity>/` + port в `Domain/Contracts/Services/<Entity>/` |
| Валидация и сборка Data из строки | `<Feature>/Application/Factories/` + port в `Domain/Contracts/Factories/`; вход — typed row DTO, сырой row array остается в `Infrastructure/Imports/*/Mappers` |
| Выбор import/export adapter-а по enum/типу входящего запроса | port в `Domain/Contracts/Factories/`, adapter в `<Feature>/Infrastructure/Factories/` или provider closure |
| Read query к БД | port в `Domain/Contracts/Repositories/`, adapter в `Infrastructure/Repositories/` |
| Запись в БД | port в `Domain/Contracts/Commands/`, adapter в `Infrastructure/Commands/` |
| Excel import | adapter в `Infrastructure/Imports`, post-row service в `Application/Services` |
| Excel export | adapter в `Infrastructure/Exports`, rows/expanders в `Application/Services` |
| RabbitMQ inbound | `Infrastructure/Messaging/Handlers` + `Infrastructure/Messaging/Validators` |
| RabbitMQ outbound | port в `Domain/Contracts/Notifications`, adapter в `Infrastructure/Notifications` |
| REST CRM read API | `Catalog/Presentation/Http/Controllers/<Entity>CrmController` + read use case/query service + presenter/response DTO |
| Межфичевый sync client | локальный port у потребителя + adapter в `Infrastructure/Clients` |
| Domain event | `Domain/Events` фичи или `<Module>/Shared/Domain/Events` для публичного факта |
| Разовый catalog fix | `Maintenance/Presentation/Console/Commands` + `Maintenance/Application/Services` |

## Примеры кода

### Port в Domain

```php
<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface UpsertVehicleFromRowServiceInterface
{
    public function upsertFromRow(VehicleSheetRowDTO $row): VehicleData;
}
```

### Application service через ports

```php
<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

final readonly class UpsertVehicleFromRowService implements UpsertVehicleFromRowServiceInterface
{
    public function __construct(
        private VehicleCommandInterface $vehicles,
        private VehicleRepositoryInterface $vehicleRepository,
    ) {
    }

    public function upsertFromRow(VehicleSheetRowDTO $row): VehicleData
    {
        $data = VehicleData::from($row);
        $existing = $this->vehicleRepository->findByMsId($data->msId);

        return $existing === null
            ? $this->vehicles->create($data)
            : $this->vehicles->update($data);
    }
}
```

### Infrastructure repository возвращает Data, не Eloquent

```php
<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Vehicle;

final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    public function findByMsId(int $msId): ?VehicleData
    {
        $vehicle = Vehicle::query()
            ->where('ms_id', $msId)
            ->first();

        return $vehicle === null ? null : VehicleData::from($vehicle);
    }
}
```

### Messaging handler как infrastructure adapter

```php
<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Messaging\Handlers;

use App\Modules\Vehicles\Features\Import\Application\UseCases\External\StartExternalFileImportUseCase;
use App\Modules\Vehicles\Features\Import\Infrastructure\Messaging\Validators\ImportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

final readonly class ImportFileRequestedHandler
{
    public function __construct(
        private ImportFileRequestedPayloadValidator $validator,
        private StartExternalFileImportUseCase $useCase,
    ) {
    }

    public function handle(array $payload): void
    {
        $validated = $this->validator->validate($payload);

        if ($validated === null) {
            Log::error('Invalid vehicles import payload.', ['payload' => $payload]);

            return;
        }

        $this->useCase->execute($validated);
    }
}
```

## Anti-Patterns

- Не возвращать Eloquent-модели из repositories наружу.
- Не импортировать чужие `Application\Services`, `Application\Factories`, presenters или feature-local `ModelData`.
- Не класть feature-local `Data`, services, repositories или Eloquent-модели в module-level `Shared`.
- Не смешивать Excel/RabbitMQ/S3/cache детали с Domain.
- Не писать бизнес-логику в Artisan command, controller, listener или message handler.
- Не заводить `Command` в read-only export-фиче.
- Не использовать события там, где нужен синхронный ответ.
- Не смешивать CRM REST read API с write/mutation flow.
- Не писать голые `$row[0]`/`$row[1]` в import parsing; только именованные `private const int`.
- Не подставлять provider/source defaults в `Data` и Application service.
- Не делать provider ownership auto-correction; это validation/domain error.
- Не хранить container-resolved services/repositories/clients/loggers в свойствах queued Excel import
  adapters.
- Не регистрировать closure listeners в `registerEvents()` у queued imports.
