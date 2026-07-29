# План: перенос домена применяемости из `dan-center`

> Файл назван `apllicapility.md` по текущему запросу. В коде/неймспейсах использовать правильное
> имя `Applicability`.

## Решение по границе

Применяемость переносим как отдельный модуль:

```text
app/Modules/Applicability/
```

Это не часть `Vehicles` и не часть `Warehouse`, потому что домен связывает обе стороны:

- `Warehouse` даёт товарную сущность: `Kit`, `Nomenclature`, состав набора, товарные `details`;
- `Vehicles` даёт транспортную сущность: `Vehicle`, `Modification`, `Engine`, `PartSpecification`;
- `Applicability` владеет правилом "какой kit применим к какой vehicle-side сущности".

`Applicability` не должен импортировать чужие `Application`/`Infrastructure` напрямую. Внутри модуля
делаем локальные порты `Domain/Contracts/Clients/*`, а adapter'ы в `Infrastructure/Clients` читают
нужные данные из Warehouse/Vehicles/Templates и переводят их в локальные `Data`.

## Что найдено в `dan-center`

### Переносим

| Источник `dan-center` | Что делает | Цель в `dan-vehicles` |
|---|---|---|
| `Services/Warehouse/Kit/KitApplicabilityService.php` | Пакетно считает применяемость активных комплектов, кроме brake pads и wiper adapter | `Applicability/Features/Calculation/Application/UseCases/CalculateKitApplicabilityUseCase` |
| `Services/Warehouse/Kit/Applicability/ApplicabilityServiceFactory.php` | Выбирает алгоритм по типу kit | Selector/factory по `NomenclatureDetailTemplateEnum`/локальному enum типа расчёта |
| `.../Wiper/*` | Извлекает длины/адаптеры из kit, ищет vehicle `PartSpecification` по wiper details | `Calculation/Application/Services/Wiper/*` |
| `.../SparkPlug/*` | Извлекает параметры свечи из kit, ищет подходящие engines через engine spark-plug specs | `Calculation/Application/Services/SparkPlug/*` |
| `Imports/Warehouse/KitApplicabilityImport.php` | XLSX импорт ручной применяемости `ms_id/mod_id/kit_id` для колодок/фильтров | отдельная feature `Applicability/Features/Import` |
| `Exports/Vehicles/VehicleKitApplicabilityExport.php` | Экспорт kit -> vehicle spec | `Applicability/Features/Export` |
| `Exports/Vehicles/EngineKitApplicabilityExport.php` | Экспорт kit -> engine | `Applicability/Features/Export` |
| `Events/Warehouse/KitApplicabilityCalculated.php` | Факт завершения расчёта | `Applicability/Features/Calculation/Domain/Events/KitApplicabilityCalculated` |
| `Events/Warehouse/KitApplicabilityImportCompleted.php` | Факт завершения импорта применяемости | `Applicability/Features/Import/Domain/Events/KitApplicabilityImportCompleted` |
| `Jobs/Warehouse/KitApplicabilityJob.php` | Queue wrapper для расчёта | `Calculation/Infrastructure/Jobs/CalculateKitApplicabilityJob` |
| `Listeners/Warehouse/ExportKitApplicabilityErrors.php` | Отчёт по ошибкам расчёта | `Calculation/Application/Listeners/ReportKitApplicabilityCalculationResultListener` + infra export/notification |

### Не переносим 1:1

| Источник `dan-center` | Почему |
|---|---|
| `trash_kit_applicability` и `Models/Vehicles/TrashKitApplicability.php` | Это ручной CRM/MpCard workaround: карточка маркетплейса может иметь свою применяемость. В `dan-vehicles` нет MpSale, значит не тащим таблицу как часть core domain. |
| `kit_applicabilitables.has_mp_card` | CRM-specific state. В core применяемости не нужен. |
| `Jobs/*/InvalidateMpCardsBy*Job.php` | Это реакция CRM на изменения Vehicles/Warehouse/Applicability. В `dan-vehicles` максимум публикуем события; CRM потом подпишется. |
| `Services/MpSale/MpCard/Params/Resolvers/WiperApplicabilityResolver.php` | Проверяет применимость при генерации marketplace params. Это MpSale consumer, не core applicability. Можно переиспользовать алгоритм later через public client. |
| `nomenclature_applicabilitables` | Старый эксперимент; таблица фактически удалялась/ошибочно дропалась как `nomanclature_applicabilitables`. Не переносим. |
| Filament actions/forms/tables | UI dan-center. В `dan-vehicles` переносим backend-сценарии и команды/handlers, UI отдельно если понадобится. |

## Важные отличия текущего `dan-vehicles`

- Код уже в `app/Modules/*`, поэтому новый модуль сразу создаём module-first.
- `part_specifications.template` теперь строка `DetailTemplateEnum`, а не FK `detail_template_id`.
  Старые запросы через `DetailTemplate::where(...)->pluck('id')` заменить на `template =
  DetailTemplateEnum::WIPER/SPARK_PLUGS`.
- `Templates` уже содержит typed details:
  - `Templates\Domain\ModelData\Nomenclature\WiperDetailsData`;
  - `Templates\Domain\ModelData\Nomenclature\WiperAdapterDetailsData`;
  - `Templates\Domain\ModelData\Nomenclature\SparkPlugDetailsData`;
  - `Templates\Domain\ModelData\Vehicle\WiperDetailsData`;
  - `Templates\Domain\ModelData\Engine\SparkPlugDetailsData`.
- `Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum` уже умеет отражать тип топлива. Для
  spark plug поиска лучше использовать его доменное правило `needsSparkPlugs()`, а не копировать
  старый список `SPARK_PLUG_FUEL_TYPES`.
- Старый pivot `kit_applicabilitables` хранит polymorphic class-string. В новом модуле лучше
  хранить стабильный enum target type, иначе БД будет зависеть от namespace Eloquent-моделей.

## Целевая схема БД

Новая module-level миграция в:

```text
app/Modules/Applicability/Shared/Infrastructure/Database/Migrations/
```

Основная таблица:

```text
kit_applicabilities
  id
  kit_id                  FK kits.id, cascade
  target_type             enum string: engine|modification|part_specification
  target_id               bigint
  source                  enum string: calculated|imported|manual
  algorithm               nullable string: wiper|spark_plugs|manual_xlsx
  created_at
  updated_at

unique: kit_id + target_type + target_id
index: target_type + target_id
index: source
```

Почему не `morphs()`:

- class-string `App\Models\Vehicles\Engine` из `dan-center` не переживает module rename;
- cross-module wire/db contract должен быть стабильным;
- `target_type` enum проще для событий, экспорта и внешних consumers.

`has_mp_card` не переносим. Если CRM понадобится связать карточку с применяемостью, она хранит свой
foreign key/связь у себя.

## Целевая структура модуля

```text
app/Modules/Applicability/
  Shared/
    Domain/
      Enums/ApplicabilityTargetTypeEnum.php
      Events/KitApplicability/KitApplicabilityCreated.php
      Events/KitApplicability/KitApplicabilityDeleted.php
      Events/KitApplicability/KitApplicabilityRecalculated.php
    Infrastructure/
      Database/Migrations/*create_kit_applicabilities_table.php
      Providers/ApplicabilityServiceProvider.php

  Features/Calculation/
    Domain/
      Contracts/Clients/WarehouseKitClientInterface.php
      Contracts/Clients/VehiclesApplicabilityClientInterface.php
      Contracts/Commands/KitApplicabilityCommandInterface.php
      Contracts/Repositories/KitApplicabilityRepositoryInterface.php
      Contracts/Services/KitApplicabilityCalculatorInterface.php
      Contracts/Services/Wiper/WiperApplicabilityServiceInterface.php
      Contracts/Services/SparkPlug/SparkPlugApplicabilityServiceInterface.php
      DTOs/Wiper/WiperLengthDTO.php
      DTOs/Wiper/WiperAdaptersDTO.php
      DTOs/SparkPlug/SparkPlugDTO.php
      DTOs/Calculation/KitApplicabilityCalculationResultDTO.php
      Enums/ApplicabilitySourceEnum.php
      Enums/KitApplicabilityAlgorithmEnum.php
      ModelData/KitData.php
      ModelData/NomenclatureData.php
      ModelData/VehiclePartSpecificationData.php
      ModelData/EngineData.php
    Application/
      UseCases/CalculateKitApplicabilityUseCase.php
      Services/KitApplicabilityCalculator.php
      Services/ApplicabilityServiceFactory.php
      Services/Wiper/*
      Services/SparkPlug/*
      Listeners/ReportKitApplicabilityCalculationResultListener.php
    Infrastructure/
      Clients/WarehouseKitClient.php
      Clients/VehiclesApplicabilityClient.php
      Commands/KitApplicabilityCommand.php
      Repositories/KitApplicabilityRepository.php
      Jobs/CalculateKitApplicabilityJob.php
      Models/KitApplicability.php
      Providers/CalculationServiceProvider.php
    Presentation/
      Console/Commands/CalculateKitApplicabilityCommand.php

  Features/Import/
    Domain/
      Contracts/Imports/KitApplicabilityImportInterface.php
      Contracts/Clients/WarehouseKitClientInterface.php
      Contracts/Clients/VehiclesModificationClientInterface.php
      DTOs/KitApplicabilityImportRowDTO.php
      Events/KitApplicabilityImportCompleted.php
    Application/
      Services/ImportKitApplicabilityRowService.php
      Listeners/ReportKitApplicabilityImportResultListener.php
    Infrastructure/
      Imports/KitApplicabilityImport.php
      Clients/WarehouseKitClient.php
      Clients/VehiclesModificationClient.php
      Providers/ImportServiceProvider.php

  Features/Export/
    Domain/
      Contracts/Exports/VehicleKitApplicabilityExportInterface.php
      Contracts/Exports/EngineKitApplicabilityExportInterface.php
      Contracts/Repositories/KitApplicabilityExportRepositoryInterface.php
    Application/
      Services/VehicleKitApplicabilityExportService.php
      Services/EngineKitApplicabilityExportService.php
    Infrastructure/
      Exports/VehicleKitApplicabilityExport.php
      Exports/EngineKitApplicabilityExport.php
      Repositories/KitApplicabilityExportRepository.php
      Providers/ExportServiceProvider.php
```

## Потоки поведения

### 1. Автоматический расчёт

Вход:

- console command `applicability:calculate-kits {--kit-id=} {--chunk=1000} {--queue}`;
- queue job `CalculateKitApplicabilityJob`;
- события на пересчёт **не подключаем на первом этапе**. После проверки производительности можно
  отдельно добавить listeners на изменения Warehouse/Vehicles.

Алгоритм:

1. `CalculateKitApplicabilityUseCase` получает `userId/runId/chunk/kitId`.
2. `WarehouseKitClientInterface` отдаёт active kits with nomenclatures как локальные `KitData`.
3. `ApplicabilityServiceFactory` выбирает алгоритм:
   - `wiper` для kits с `NomenclatureDetailTemplateEnum::WIPER`;
   - `spark_plugs` для kits с `NomenclatureDetailTemplateEnum::SPARK_PLUGS`;
   - остальные типы пока пропускаются или обрабатываются только imported/manual workflow.
4. Algorithm возвращает набор target references:
   - wiper: `target_type=part_specification`, `target_id=<vehicle part_specification id>`;
   - spark plugs: `target_type=engine`, `target_id=<engine id>`.
5. `KitApplicabilityCommandInterface::syncCalculatedTargets()` делает атомарный replace targets для
   `kit_id + algorithm`, не трогая `source=imported/manual`.
6. Публикуется `KitApplicabilityRecalculated` с `runId`, stats и affected kit ids.

### 2. Ручной XLSX import

Перенос старого `KitApplicabilityImport`:

- sheets: `Колодки`, `Масляные фильтры`, `Воздушные фильтры`;
- row columns: `ms_id`, `mod_id`, `kit_id`;
- resolver отрицательного `ms_id` оставить как доменное правило Vehicles boundary:
  локальный `VehiclesModificationClientInterface::resolveByMsAndModId(int $msId, int $modId)`.

Запись:

- `target_type=modification`;
- `target_id=<modification id>`;
- `source=imported`;
- `algorithm=manual_xlsx`.

Idempotency/cache:

- не использовать cache key по `userId`, как в `dan-center`;
- использовать `ImportRunContextDTO(userId, runId)` по текущему правилу архитектуры;
- ошибки сохранять/экспортировать так же, как остальные imports в `Warehouse/Import` и
  `Vehicles/Import`.

### 3. Экспорт

Перенести два отчёта как `Applicability/Features/Export`:

- `VehicleKitApplicabilityExport`: rows по `target_type=part_specification`, join/read через
  Vehicles client/repository, включая vehicle info;
- `EngineKitApplicabilityExport`: rows по `target_type=engine`, включая `eng_id`, `code_engine`.

Старую прямую загрузку `PartSpecification::with(['kits.nomenclatures', 'partable'])` заменить на
read repository, который отдаёт export rows/Data без Eloquent-моделей наружу.

### 4. Реакции на события

В `dan-center` это было observer -> MpCard invalidation jobs. В `dan-vehicles` observer'ы не
используем.

Первый этап: без автоматических listeners. Расчёт запускается вручную через command или ставится в
queue.

После проверки производительности можно добавить слушатели `Applicability`:

- `KitCreated/KitUpdated` -> recalculate kit;
- `KitDeleted` -> delete all applicability rows for kit;
- `NomenclatureUpdated/Deleted` -> affected kits -> recalculate/delete rows;
- `VehicleUpdated/Deleted`, `ModificationUpdated/Deleted`, `EngineUpdated/Deleted`,
  `PartSpecificationUpdated/Deleted` -> invalidate/delete affected applicability rows.

Для delete-событий важно использовать внутренний id (`vehicleId`, `engineId`, `partSpecificationId`,
`kitId`), а не только external id. Если какого-то id нет в shared event, сначала доработать event
payload в owner module.

CRM/MpSale later:

- не добавлять CRM jobs в `dan-vehicles`;
- вместо этого публиковать `KitApplicabilityRecalculated/Deleted` и дать CRM подписаться через
  свой транспорт/consumer.

## Перенос алгоритмов: важные правки

### Wiper

Старое:

- читает `Kit` Eloquent + `nomenclatures`;
- различает типы по `TypeEnum::WIPER/WIPER_ADAPTER`;
- ищет vehicle specs через `DetailTemplate` FK;
- пишет `$kit->partSpecifications()->sync(...)`.

Новое:

- `WiperDataExtractor` принимает `KitData`, не Eloquent;
- тип номенклатуры определять через `NomenclatureDetailTemplateEnum`, а не hardcoded `type_id`;
- details парсить через `Templates\Domain\ModelData\Nomenclature\WiperDetailsData` и
  `WiperAdapterDetailsData`;
- vehicle specs искать по `part_specifications.template = DetailTemplateEnum::WIPER->value`;
- side detection/normalization вынести в локальный сервис или в Templates public client. Если
  нужен существующий `Templates\Application\WiperSpecificationService`, вызывать его только через
  локальный port adapter, не напрямую из Application `Applicability`.
- старый bug-risk: `array_intersect_key($putAdapters, $resultAdapters)` сравнивает numeric keys,
  не значения. При переносе покрыть тестом и заменить на value-intersection.

### Spark plugs

Старое:

- берёт первую номенклатуру kit;
- читает `thread.size/pitch/length`, `electrode.gap`, `wrench_jaw_width`;
- ищет engines по fuel type, cylinder_count и engine `PartSpecification` details;
- пишет `$kit->engines()->sync(...)`.

Новое:

- extractor принимает `KitData`;
- details парсить через `Templates\Domain\ModelData\Nomenclature\SparkPlugDetailsData`;
- engine specs искать по `part_specifications.template = DetailTemplateEnum::SPARK_PLUGS->value`;
- fuel filter строить через `EngineFuelTypeEnum::needsSparkPlugs()`;
- результат писать через `KitApplicabilityCommandInterface`, target `engine`.

## Пошаговый план работ

1. **Создать skeleton модуля**
   - `app/Modules/Applicability/Shared/...`
   - root provider + feature providers;
   - подключить provider в `bootstrap/providers.php`;
   - добавить command path в `bootstrap/app.php` при появлении console commands.

2. **Добавить миграцию `kit_applicabilities`**
   - enum-like string columns `target_type`, `source`, `algorithm`;
   - уникальность `kit_id + target_type + target_id`;
   - индексы под reverse lookup.

3. **Добавить Domain contracts/Data/DTO/enums**
   - локальные `KitData`, `NomenclatureData`, `VehiclePartSpecificationData`, `EngineData`;
   - `WiperLengthDTO`, `WiperAdaptersDTO`, `SparkPlugDTO`;
   - `ApplicabilityTargetTypeEnum`, `ApplicabilitySourceEnum`, `KitApplicabilityAlgorithmEnum`.

4. **Сделать infrastructure clients**
   - `WarehouseKitClient`: читает kits, kit_nomenclature, nomenclatures, type info/templates;
   - `VehiclesApplicabilityClient`: читает vehicle part specs, engines, engine specs,
     modification resolver;
   - без импортов чужих `Application`; наружу только локальные Data.

5. **Перенести automatic calculation**
   - `KitApplicabilityCalculator`;
   - wiper extractor/finder/service;
   - spark plug extractor/finder/service/cache;
   - command `syncCalculatedTargets()`;
   - queue job + console command.

6. **Перенести manual XLSX import**
   - `KitApplicabilityImport`;
   - row service;
   - failure reporting через runId/cache по текущему Import pattern;
   - sheets `Колодки`, `Масляные фильтры`, `Воздушные фильтры`.

7. **Перенести exports**
   - vehicle applicability report;
   - engine applicability report;
   - заменить прямой Eloquent на repository/export row Data.

8. **Отложить event-autorecalc**
   - на первом этапе не подписывать `Applicability` на события Warehouse/Vehicles;
   - запуск только через command + queue;
   - после проверки производительности вернуться к listeners на `KitCreated/KitUpdated/KitDeleted`,
     `NomenclatureUpdated/Deleted` и Vehicles-side events;
   - observer'ы не использовать.

9. **Тесты**
   - unit: `WiperLengthExtractor`, `WiperAdapterExtractor`, `WiperVehicleFinder::checkAdapters`;
   - unit: `SparkPlugExtractor`, `SparkPlugEngineFinder` фильтры;
   - feature: calculation writes `kit_applicabilities` for wiper kit;
   - feature: calculation writes engine targets for spark plug kit;
   - feature: manual import resolves negative `ms_id` via parent vehicle;
   - feature: events trigger recalculation/delete;
   - regression: adapter value intersection для `putAdapters`.

10. **Очистка/совместимость**
    - не добавлять morph relations `Kit::engines()/partSpecifications()` в чужие modules;
    - если старые consumers ждут `kit_applicabilitables`, сделать временный read adapter/view или
      миграцию данных, но не возвращать class-string morphs как новый контракт;
    - документацию архитектуры обновить правилом: `Applicability` владеет cross-domain fitment,
      а `Vehicles/Warehouse` только публикуют события и дают read clients.

## Принятые решения

1. **Решено:** `trash_kit_applicability` не переносим. Это CRM/MpSale-specific workaround, не core
   применяемость.
2. **Решено:** первый запускной контур — command + queue. Event-autorecalc добавить отдельным
   этапом только после проверки производительности.
3. **Решено:** targets только `PartSpecification`, `Modification`, `Engine`. `Vehicle` target не
   нужен и в enum не добавляется.
4. **Решено:** историю расчётов не храним. Достаточно latest-state table `kit_applicabilities` и
   result event/отчёта по текущему запуску.
