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
      UseCases/             # External scenario ports
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
    Factories/              # Row mapping, selectors, Data builders
    Listeners/              # Тонкие реакции на domain events
  Infrastructure/
    Models/                 # Feature-local Eloquent models
    Repositories/           # Read adapters
    Commands/               # Write adapters
    Imports/                # Excel import adapters
    Exports/                # Excel export adapters
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
- `Presentation` парсит вход, валидирует entrypoint-level параметры и вызывает use case/service port.
- Межфичевый sync-вызов идет через локальный `Domain/Contracts/Clients/*ClientInterface` фичи-потребителя и adapter в ее `Infrastructure/Clients`.
- События используются только для фактов без return value; если нужен ответ сразу, это client/query contract, не event.
- `Shared` внутри доменного модуля — публичная часть модуля, а не папка для общей бизнес-логики.
- Верхнеуровневый `app/Modules/Shared` не используется: технические workflow, console base classes,
  Excel helpers и adapters размещаются внутри конкретной фичи. Небольшое дублирование между
  модулями допустимо ради изоляции bounded contexts.

## Коммуникация слоев и модулей

- Cross-feature факт внутри модуля лежит в `<Module>/Shared/Domain/Events`.
- Внутренний факт фичи лежит в `<Feature>/Domain/Events`.
- Request/result workflow оформляется двумя сообщениями: request/fact event и result event с `runId` или `correlationId`.
- RabbitMQ inbound: `Infrastructure/Messaging/Handlers/*Handler` валидирует payload через `Messaging/Validators`, собирает DTO и вызывает use case.
- RabbitMQ outbound: `Infrastructure/Notifications/*NotificationService` публикует explicit notification DTO.
- Excel import: adapter в `Infrastructure/Imports` читает файл и делегирует построчную работу Application service.
- Queued Excel import: import adapter не хранит service/repository/client/logger dependency graph в
  свойствах. Для классов `ShouldQueue` зависимости резолвятся в `collection()` или другом worker-time
  методе, а `registerEvents()` не использует closures.
- Excel export: adapter в `Infrastructure/Exports` читает данные через Repository и собирает строки через Application services/factories.
- Maintenance-фичи могут ходить в Eloquent напрямую из своих services/commands, если это осознанный разовый catalog fix без reusable Repository boundary.

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
- `ModelData` — снимки строк/сущностей через `spatie/laravel-data`; enum-поля типизируются реальными enum-классами.
- `ModelData` не содержит import/export/create/update методов и не превращается в rich entity.
  Бизнес-сценарии остаются в Application.
- Events — `final readonly` факты в прошедшем времени, без поведения и без суффикса `Event`, если это соответствует существующему неймингу.
- Общие enum-словарь и wire/db contracts кладутся в module-level `Shared/Domain/Enums`, а workflow-local enum остается в фиче.

### Application

- Оркестрирует сценарии и прикладные правила.
- Каждый инъектируемый service/use case/factory должен иметь интерфейс в `Domain/Contracts`, кроме тонких listeners.
- Listeners остаются в `Application`, не в `Infrastructure`, если это in-process реакция на domain event.
- Application не использует `Model::query()`, `updateOrCreate()`, `save()` и другие Eloquent operations, кроме явно выделенных Maintenance-исключений.

### Infrastructure

- Eloquent-модели feature-local и анемичные: relations, casts, timestamps, без бизнес-логики.
- Repository — только чтение, возвращает `Data`, `Collection<int, Data>` или `Generator<int, Data>`.
- Command — запись, принимает `Data`/DTO и инкапсулирует save/upsert/delete.
- Messaging, Files, Cache, Storage, Jobs, Notifications и external clients остаются здесь.
- Laravel Excel adapters с `ShouldQueue` должны быть сериализуемыми: свойства — только scalar/DTO/value
  state; application services, repositories, clients и loggers резолвятся во время выполнения job.
- Event listeners для queued imports регистрируются как сериализуемые callables, например
  `[self::class, 'afterImport']`, а не closure.

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

Отложено для отдельного прохода:

- `Warehouse/Features/MoySklad`: отдельно проверить границы под-контекста, прямые concrete
  Application dependencies, mapper/service ports и правила интеграции. До этого прохода не
  смешивать MoySklad-рефакторинг с общими архитектурными исправлениями.

## Куда класть новое

| Что добавляется | Куда класть |
|---|---|
| Новый сценарий с внешним триггером | `<Feature>/Application/UseCases/<Group>/` + port в `Domain/Contracts/UseCases/` |
| Новое прикладное правило | `<Feature>/Application/Services/<Entity>/` + port в `Domain/Contracts/Services/<Entity>/` |
| Валидация и сборка Data из строки | `<Feature>/Application/Factories/` + port в `Domain/Contracts/Factories/` |
| Read query к БД | port в `Domain/Contracts/Repositories/`, adapter в `Infrastructure/Repositories/` |
| Запись в БД | port в `Domain/Contracts/Commands/`, adapter в `Infrastructure/Commands/` |
| Excel import | adapter в `Infrastructure/Imports`, post-row service в `Application/Services` |
| Excel export | adapter в `Infrastructure/Exports`, rows/expanders в `Application/Services` |
| RabbitMQ inbound | `Infrastructure/Messaging/Handlers` + `Infrastructure/Messaging/Validators` |
| RabbitMQ outbound | port в `Domain/Contracts/Notifications`, adapter в `Infrastructure/Notifications` |
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

interface ImportVehicleFromRowServiceInterface
{
    public function import(VehicleSheetRowDTO $row): void;
}
```

### Application service через ports

```php
<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\ImportVehicleFromRowServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

final readonly class ImportVehicleFromRowService implements ImportVehicleFromRowServiceInterface
{
    public function __construct(
        private VehicleCommandInterface $vehicles,
    ) {
    }

    public function import(VehicleSheetRowDTO $row): void
    {
        $this->vehicles->upsert(VehicleData::from($row));
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

use App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Vehicles\Features\Import\Infrastructure\Messaging\Validators\ImportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

final readonly class ImportFileRequestedHandler
{
    public function __construct(
        private ImportFileRequestedPayloadValidator $validator,
        private StartExternalFileImportUseCaseInterface $useCase,
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
- Не хранить container-resolved services/repositories/clients/loggers в свойствах queued Excel import
  adapters.
- Не регистрировать closure listeners в `registerEvents()` у queued imports.
