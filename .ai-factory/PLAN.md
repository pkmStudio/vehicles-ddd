# Implementation Plan: исправление архитектурных границ без MoySklad

Branch: master
Created: 2026-08-01

## Settings

- Testing: yes
- Logging: standard
- Docs: yes

## Research Context

Source: архитектурный разбор текущего проекта и обновлённые `ARCHITECTURE.md` / `.ai-factory/ARCHITECTURE.md`.

Goal: привести согласованные архитектурные долги к целевым правилам без отказа от Laravel и без
рефакторинга `Warehouse/Features/MoySklad`.

Constraints:
- Laravel framework coupling остаётся допустимым компромиссом.
- `ModelData` остаются тонкими snapshots; бизнес-логика остаётся в Application.
- `Warehouse/Features/MoySklad` не трогать в рамках этого плана.
- Не делать один большой style-only PR; стиль править рядом с изменяемым кодом.

Decisions:
- Driving adapters должны идти через use case/service ports.
- Broker/storage детали должны жить за Infrastructure adapters.
- `Templates` считается shared-kernel; `WiperSpecificationService` не должен писать в Laravel `Log` напрямую.
- `Shared` module-level правила обновлены и допускают public client contracts/DTOs/adapters без бизнес-логики.

Open questions:
- Какой контракт возврата нужен CRM HTTP endpoints после выноса `VehicleCrmController`: оставить текущий JSON payload полностью совместимым или ввести explicit response DTO.
- Логирование нарушения инварианта в `WiperSpecificationService`: делать через PSR logger в Application или вынести в вызывающий сценарий.

## Commit Plan

- **Commit 1** (after tasks 1-3): `refactor: move crm vehicle reads behind use cases`
- **Commit 2** (after tasks 4-5): `refactor: route local import requests through ports`
- **Commit 3** (after tasks 6-8): `refactor: remove application logging facade debt`

## Tasks

### Phase 1: Vehicles CRM read boundary

- [x] Task 1: Проверить уже начатые изменения вокруг CRM read flow и не перетереть чужую работу.
  Files: `app/Modules/Vehicles/Features/Catalog/Presentation/Http/Controllers/VehicleCrmController.php`,
  `app/Modules/Vehicles/Features/Catalog/Application/UseCases/Vehicle/*VehicleForCrmUseCase.php`,
  `app/Modules/Vehicles/Features/Catalog/Domain/Contracts/*`,
  `app/Modules/Vehicles/Features/Catalog/Infrastructure/Repositories/VehicleCrmReadRepository.php`,
  `app/Modules/Vehicles/Features/Catalog/Infrastructure/Providers/CatalogServiceProvider.php`.
  Logging requirements: не добавлять новые логи на этом шаге; только инвентаризация поведения,
  совместимости JSON и существующих DI bindings.

- [x] Task 2: Завершить вынос SQL/filter/search/sort/pagination из `VehicleCrmController` в
  Application use cases и `VehicleCrmReadRepositoryInterface`.
  Expected behavior: controller выполняет guard/request parsing, вызывает use case port и возвращает
  прежнюю JSON-форму `data/meta`; repository инкапсулирует `DB::table`, joins, filters, search,
  sorting и pagination.
  Logging requirements: WARN/ERROR не нужны для успешных read-запросов; при невозможности собрать
  query из входных параметров использовать обычный validation/response flow без шумного логирования.

- [x] Task 3: Добавить или обновить focused tests для CRM endpoints/repository behavior.
  Files: `tests/Feature` или `tests/Unit` по существующей структуре проекта.
  Expected behavior: покрыть list/show/search, unauthorized guard, not found, фильтры, поиск и sort.
  Logging requirements: тесты не должны зависеть от логов; если добавляются проверки ошибок,
  логирование проверять только там, где оно является контрактом поведения.

### Phase 2: Shared local import request boundary

- [x] Task 4: Спроектировать port/use case для публикации локального import request без прямого
  `RabbitMQPublisher`/`Storage` в Presentation.
  Files: `app/Modules/Shared` или более точный feature/module после выбора владельца сценария.
  Expected behavior: command парсит `path/disk/user-id/operation-id`, вызывает Application port;
  storage existence и broker publish выполняются через Infrastructure adapters.
  Logging requirements: INFO при успешной публикации, ERROR/WARN при невозможности найти файл или
  опубликовать сообщение; не логировать секреты и содержимое файлов.

- [x] Task 5: Переписать `RequestLocalImportCommand` на новый port/use case и сохранить текущую CLI
  совместимость.
  Files: `app/Modules/Shared/Presentation/Console/Commands/RequestLocalImportCommand.php` и новые
  Domain/Application/Infrastructure классы выбранного владельца.
  Expected behavior: все наследники команды работают с теми же arguments/options и публикуют тот же
  RabbitMQ payload.
  Logging requirements: command выводит пользовательские console messages; infrastructure adapter
  логирует технический сбой публикации на ERROR с `event`, `routing_key`, `operation_id`, `disk`,
  `path`.

### Phase 3: Templates logging boundary

- [x] Task 6: Убрать прямой `Log` facade из `Templates/Application/WiperSpecificationService`.
  Files: `app/Modules/Templates/Application/WiperSpecificationService.php`,
  `app/Modules/Templates/Domain/Contracts/*`,
  `app/Modules/Templates/Infrastructure/Providers/TemplatesServiceProvider.php`.
  Expected behavior: сервис остаётся чистым относительно Laravel facade; предупреждение о нескольких
  adapter values не теряется.
  Logging requirements: если выбран PSR logger, логировать WARN с `part_specification_id`, `side`,
  `adapter_count`, `adapters`; если выбран вынос наружу, вызывающий сценарий логирует тот же context.

- [x] Task 7: Обновить tests для `WiperSpecificationService` и затронутых callers.
  Files: существующие `tests/Unit/Templates` или ближайшая актуальная директория тестов.
  Expected behavior: нормализация adapters сохраняет данные; предупреждение/сигнал нарушения
  инварианта покрыт тестом без зависимости от Laravel facade.
  Logging requirements: в тестах использовать fake/mock logger или проверять возвращаемый сигнал,
  если логирование вынесено наружу.

### Phase 4: Local style cleanup near touched code

- [x] Task 8: Привести только затронутые файлы к правилам докблоков, inline `new DTO(...)` и
  многострочных именованных аргументов.
  Files: только файлы из задач 1-7 и их непосредственные новые contracts/DTOs/tests.
  Expected behavior: не запускать массовый formatting/refactor по 1100 файлам.
  Logging requirements: новых логов не требуется; существующие логи оставить с понятным context и
  без чувствительных данных.
