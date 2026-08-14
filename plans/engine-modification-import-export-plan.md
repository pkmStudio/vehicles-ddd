# План импорта/экспорта двигателей, модификаций и связей

Дата: 2026-08-12.

## Контекст

- `dan-vehicles` владеет каталогом Vehicles и остается headless-сервисом.
- `dan-center` владеет Filament UI.
- Read-сценарии идут по REST, write/heavy-сценарии идут через RabbitMQ.
- Для менеджерских массовых операций нужны отдельные Excel-файлы:
  - двигатели;
  - модификации;
  - связи модификаций с двигателями.
- `engines.eng_id` проверен на локальной БД: 30 560 строк, `null_eng_id=0`, дублей `eng_id=0`.
- `storage/vehicles/modifications.csv` проверен нормальным CSV-парсером: дублей по `mod_id + type`
  нет. Это natural key модификации.
- Модификаций с двумя и более двигателями много: 25 176, максимум 18 двигателей на одну модификацию. Поэтому связь нельзя удобно держать одной колонкой внутри листа модификаций.
- TecDoc-импорт остается отдельным системным импортом и может перетереть все данные. Ограничения `provider/allow_change_fields` применяются к менеджерским операциям, но не к TecDoc-импорту.

## Итоговое решение

## Архитектурные правила реализации

- Новые изменения делать по module-first / feature-first структуре:
  `app/Modules/Vehicles/Features/{Import,Export,Catalog}/{Domain,Application,Infrastructure}`.
- Excel adapters размещать только в `Infrastructure/Imports` и `Infrastructure/Exports`.
- Выбор concrete import/export adapter по enum/import_type/export_type держать в
  `Infrastructure/Factories` или provider boundary за domain-портом.
- Application не должен работать с Eloquent напрямую. Чтение идет через `RepositoryInterface`,
  запись — через `CommandInterface`.
- Не переиспользовать `Catalog` commands/use cases внутри `Import` feature напрямую. Если manager
  import должен использовать похожие правила записи, эти правила оформляются локальным service/policy
  внутри `Import` feature или через собственный port.
- DTO/Data остаются локальными для своей фичи. `pkmstudio/dan-wire-contracts` использовать только на
  внешней REST/Rabbit boundary и в contract tests, не протаскивать package DTO в Domain/Application.
- Все queued Excel imports должны быть сериализуемыми: в свойствах только scalar/DTO/value state,
  сервисы/репозитории/логгеры резолвить во время выполнения job.
- `registerEvents()` у queued imports не должен возвращать closures; использовать сериализуемые
  callables вроде `[self::class, 'afterImport']`.
- Production `info/debug` logs для нормального успешного потока не добавлять. Логи `warning/error`
  использовать для actionable validation/preflight/integration failures.
- Для новых production методов use case/service/adapter/repository/command/factory/presenter/handler
  писать PHPDoc-блок с назначением и блоком `Шаги:`. Для простых DTO/Data/enum helpers достаточно
  короткого PHPDoc без подробного алгоритма.
- Любое изменение `details`/Templates не входит в эту задачу. Engine `details`, свечи и применяемость
  двигателей в рамках этого плана отключаются/не расширяются.

### 1. Двигатели

Делать чистый импорт/экспорт двигателей без `details`, свечей и применяемости.

Использовать существующий Rabbit event:

- import: `ENGINES_IMPORT_FILE_REQUESTED`;
- export: `ENGINES_EXPORT_FILE_REQUESTED`;
- type: `engine_multi_sheet`.

`engine_multi_sheet` в менеджерском UI должен стать чистым файлом с листом `Двигатели` и, при необходимости, справочным листом. Листы свечей/применяемости из этого файла убрать.

Колонки листа `Двигатели`:

```text
eng_id
code_engine
engine_capacity
fuel_type
power_kw_start
power_kw_upto
power_ps_start
power_ps_upto
cylinder_count
cylinder_diameter
number_of_valves
provider
```

Правила:

- `eng_id` является уникальным внешним ID двигателя.
- Для новой OD-записи `eng_id` можно оставить пустым только если сценарий генерации отрицательных ID будет явно реализован; иначе поле обязательное.
- `provider` выводится в экспорте справочно.
- Новые строки из менеджерского импорта создаются как `OD`.
- Существующие `TD` строки меняются только по правилам `allow_change_fields`: поле можно изменить, если оно уже разрешено или текущее значение `null`.
- Попытка изменить закрытое поле TD-двигателя должна попадать в xlsx-отчет ошибок, а не молча игнорироваться.

### 2. Модификации

Делать отдельный импорт/экспорт только самих модификаций, без связей с двигателями.

Использовать:

- import: существующий `MODIFICATIONS_IMPORT_FILE_REQUESTED`;
- export: добавить `MODIFICATIONS_EXPORT_FILE_REQUESTED`;
- type: добавить `modification_catalog`.

Колонки листа `Модификации`:

```text
ms_id
mod_id
localized_name
year_from
year_to
capacity_lt
engine_type
power_ps
power_kw
drive_type
gear_type
brake_system_type
number_of_cylinders
description
description_short
type
provider
```

Правила:

- `ms_id` обязателен: по нему определяется машина.
- `mod_id` для существующей модификации обязателен.
- Для новой OD-модификации `mod_id` может быть пустым, если сервис генерирует отрицательный `mod_id`.
- `type` в модификациях присваивается автоматически по машине/контексту. В экспорте поле показываем справочно.
- Если `type` пришел в импорте и не совпадает с рассчитанным значением, строка должна попасть в отчет ошибок.
- `description_short` является новым полем схемы и должно быть прокинуто через все слои перед
  включением импорта/экспорта модификаций.
- `provider` выводится в экспорте справочно.
- Новые строки создаются как `OD`.
- Для TD-модификаций действуют правила `allow_change_fields`; `year_from` и `year_to` должны храниться в разрешенных полях сразу.
- Удаление модификаций через этот импорт на первом этапе не делать. Удаление остается через Filament action/Rabbit mutation, чтобы не получить случайное массовое удаление.

### 3. Связи модификаций с двигателями

Делать отдельный импорт/экспорт связей.

Добавить новые Rabbit events и routing keys:

- import event: `ENGINE_MODIFICATIONS_IMPORT_FILE_REQUESTED`;
- export event: `ENGINE_MODIFICATIONS_EXPORT_FILE_REQUESTED`;
- import routing key: `crm.engine-modifications.import`;
- export routing key: `crm.engine-modifications.export`;
- import/export type: `engine_modifications`.

Колонки листа `Связи модификаций и двигателей`:

```text
mod_id
eng_id
type
```

Правила:

- `mod_id` должен уже существовать.
- `eng_id` должен уже существовать.
- `type` обязателен и заполняется пользователем, потому что участвует в pivot-контракте связи.
- Одна строка = одна связь `mod_id + eng_id + type`.
- Модификация находится по `mod_id + type`; это natural key, который должен быть закреплен
  уникальностью в БД и preflight-проверкой перед импортом.
- Импорт связей работает как синхронизация желаемого состояния по ключу `mod_id + type`.
- Для каждого `mod_id + type`, присутствующего в файле, итоговый набор двигателей должен стать ровно таким, как в файле.
- Удаление связи выполняется удалением строки из Excel и повторным импортом файла.
- Если `mod_id + type` не найден, строка идет в отчет ошибок.
- Если `eng_id` не найден, строка идет в отчет ошибок.
- Если `type` не соответствует типу найденной модификации, строка идет в отчет ошибок.

## План реализации

## Статус выполнения на 2026-08-13

- [x] Wire contracts расширены новыми event names/routing keys для экспорта модификаций и import/export связей модификаций с двигателями.
- [x] В `dan-vehicles` очищен `engine_multi_sheet`: основной файл двигателей больше не включает лист свечей/применяемости.
- [x] В `dan-vehicles` добавлены export types `modification_catalog` и `engine_modifications`.
- [x] В `dan-vehicles` добавлен manager import двигателей с policy `provider/allow_change_fields`; TecDoc import оставлен без этой защиты.
- [x] В `dan-vehicles` добавлен manager import модификаций с `description_short`, расчетом `type` по машине и генерацией отрицательного `mod_id`.
- [x] В `dan-vehicles` добавлен import/export связей `mod_id + eng_id + type`; import работает как синхронизация групп, присутствующих в файле.
- [x] В `dan-vehicles` добавлены completion events/cache keys/reporting wiring для новых import flows.
- [x] В `dan-center` добавлены кнопки import/export модификаций и связей, а export/import двигателей переведен на чистый catalog flow через Rabbit.
- [x] `description_short` прокинут через mutation DTO/payload, REST DTO и Filament форму.
- [x] Обновлен `plans/proverka.md` ручными сценариями для двигателей, модификаций и связей.
- [ ] Выполнить `rabbit-transport:setup` в `dan-vehicles` и `dan-center` на нужном окружении.
- [ ] Пройти ручной сценарий из `plans/proverka.md` через Filament.
- [ ] Автотесты из фаз 5.16-5.17 не добавлялись: по текущему решению это остается отдельной задачей после ручной стабилизации flow.

### Фаза 1. Wire contracts

1. Расширить `pkmstudio/dan-wire-contracts`.
   - Файлы: `/home/user/projects/packages/dan-wire-contracts/src/Vehicles/Shared/Enums/VehiclesEventName.php`, `/home/user/projects/packages/dan-wire-contracts/src/Vehicles/Shared/Enums/VehiclesRoutingKey.php`.
   - Добавить event/routing key для `MODIFICATIONS_EXPORT_FILE_REQUESTED`.
   - Добавить event/routing key для `ENGINE_MODIFICATIONS_IMPORT_FILE_REQUESTED`.
   - Добавить event/routing key для `ENGINE_MODIFICATIONS_EXPORT_FILE_REQUESTED`.
   - Добавить import/export DTO при необходимости, если текущие generic `ImportFileRequested`/`ExportFileRequested` не покрывают новый тип.
   - Логи: не требуются, это пакет контрактов.
   - Проверка: composer autoload/package tests, если они есть.

2. Обновить зависимости пакета в `dan-vehicles` и `dan-center`.
   - Файлы: `composer.json`, `composer.lock` в обоих сервисах, если package version/path требует обновления.
   - Не менять vendor руками.
   - Логи: не требуются.
   - Проверка: `composer dump-autoload`, затем targeted tests.

### Фаза 2. `dan-vehicles`: экспорт

3. Закрепить схему модификаций перед import/export flow.
   - Файлы:
     - `app/Modules/Vehicles/Shared/Infrastructure/Database/Migrations/2026_06_17_100004_create_modifications_table.php`;
     - `app/Modules/Vehicles/Features/*/Infrastructure/Models/Modification.php`;
     - `app/Modules/Vehicles/Features/*/Domain/ModelData/ModificationData.php`;
     - `app/Modules/Vehicles/Features/Catalog/Domain/DTOs/Modification/*`;
     - `app/Modules/Vehicles/Features/Catalog/Infrastructure/Messaging/Validators/ModificationMutationPayloadValidator.php`;
     - `/home/user/projects/packages/dan-wire-contracts/src/Vehicles/Modules/Vehicles/Features/Catalog/Mutation/DTO/ModificationMutationPayload.php`;
     - `/home/user/projects/dan-center/app/Filament/Resources/VehiclesRest/Schemas/VehicleRestEditForm.php`;
     - `/home/user/projects/dan-center/app/Filament/Resources/VehiclesRest/Actions/VehiclesRestMutationActions.php`.
   - Прокинуть `description_short` во все слои: schema/model fillable/casts, Data/DTO, Rabbit
     mutation payload, REST detail response и Filament form/state/payload.
   - Закрепить unique key `mod_id + type` в миграции, если пользователь уже добавил его локально —
     проверить, что все feature-local модели и commands с ним совместимы.
   - Добавить preflight-проверку локальных данных перед import связей: если в БД есть дубли
     `mod_id + type`, import связей должен завершиться ошибкой с понятным сообщением, а не выбрать
     случайную модификацию.
   - Логи: warning при обнаружении дублей natural key в preflight; error только для неожиданных
     failures.
   - Проверка: feature/unit tests на `description_short` round-trip и preflight reject дублей.

4. Выровнять чистый export двигателей.
   - Файлы:
     - `app/Modules/Vehicles/Features/Export/Infrastructure/Exports/Engine/EngineMultiSheetExport.php`;
     - `app/Modules/Vehicles/Features/Export/Application/Services/Rows/EngineExportRow.php`;
     - `app/Modules/Vehicles/Features/Export/Application/Services/EngineExportService.php`;
     - `app/Modules/Vehicles/Features/Export/Infrastructure/Repositories/EngineRepository.php`.
   - Убрать лист свечей из `engine_multi_sheet`.
   - Выровнять порядок колонок export-а с import mapper-ом.
   - Добавить `provider` в export-строку.
   - Логи: не добавлять для успешного экспорта; warning/error только для неожиданных adapter failures.
   - Проверка: feature-test export-а, что workbook содержит только ожидаемые листы и колонки.

5. Добавить export модификаций.
   - Файлы:
     - `app/Modules/Vehicles/Features/Export/Domain/Enums/ExportTypeEnum.php`;
     - `app/Modules/Vehicles/Features/Export/Domain/Contracts/Exports/*`;
     - `app/Modules/Vehicles/Features/Export/Infrastructure/Exports/Modification/*`;
     - `app/Modules/Vehicles/Features/Export/Infrastructure/Repositories/ModificationRepository.php`;
     - `app/Modules/Vehicles/Features/Export/Infrastructure/Factories/ExportFileFactory.php`;
     - `config/rabbit-transport.php`.
   - Добавить `ExportTypeEnum::ModificationCatalog`.
   - Добавить handler mapping для `MODIFICATIONS_EXPORT_FILE_REQUESTED`.
   - Экспортировать одну строку на одну модификацию.
   - Включить `description_short` после задачи схемы.
   - `type` и `provider` выводить справочно.
   - Логи: warning/error только при невозможности собрать файл.
   - Проверка: feature-test export-а по колонкам и sample row.

6. Добавить export связей `engine_modification`.
   - Файлы:
     - `app/Modules/Vehicles/Features/Export/Domain/Enums/ExportTypeEnum.php`;
     - `app/Modules/Vehicles/Features/Export/Infrastructure/Exports/EngineModification/*`;
     - `app/Modules/Vehicles/Features/Export/Infrastructure/Repositories/EngineModificationRepository.php`;
     - `app/Modules/Vehicles/Features/Export/Infrastructure/Factories/ExportFileFactory.php`;
     - `config/rabbit-transport.php`.
   - Добавить `ExportTypeEnum::EngineModifications`.
   - Добавить handler mapping для `ENGINE_MODIFICATIONS_EXPORT_FILE_REQUESTED`.
   - Экспортировать одну строку на одну связь `mod_id + eng_id + type`.
   - Логи: warning/error только при сбое генерации.
   - Проверка: feature-test, что модификация с несколькими двигателями дает несколько строк.

### Фаза 3. `dan-vehicles`: импорт

7. Подготовить reporting/completion для новых manager imports.
   - Файлы:
     - `config/vehicles/import.php`;
     - `app/Modules/Vehicles/Features/Import/Domain/Events/Modification/*`;
     - `app/Modules/Vehicles/Features/Import/Domain/Events/EngineModification/*`;
     - `app/Modules/Vehicles/Features/Import/Infrastructure/Providers/ImportEventServiceProvider.php`;
     - `app/Modules/Vehicles/Features/Import/Application/Listeners/ReportImportResultListener.php`;
     - `app/Modules/Vehicles/Features/Import/Application/Listeners/CleanupExternalImportFileListener.php`.
   - Добавить cache keys для ошибок `modification_catalog` и `engine_modifications`.
   - Добавить completion events, которые наследуют общий `AbstractImportCompleted`, чтобы
     существующий report listener сформировал xlsx-отчет и отправил `VEHICLES_IMPORT_COMPLETED`
     с `disk/path`.
   - Подключить cleanup listener для этих external imports.
   - Логи: error уже есть в reporting workflow; добавить warning только для business preflight errors.
   - Проверка: feature-test, что import с ошибками публикует result с `disk`, `path`,
     `errors_count` и xlsx artifact.

8. Выровнять чистый import двигателей.
   - Файлы:
     - `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Engine/EngineMultiSheetImport.php`;
     - `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Engine/Sheets/EngineMainSheetImport.php`;
     - `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Engine/Mappers/EngineMainSheetRowMapper.php`;
     - `app/Modules/Vehicles/Features/Import/Application/Factories/EngineDataFactory.php`;
     - `app/Modules/Vehicles/Features/Import/Application/Services/Engine/UpsertEngineFromSheetService.php`;
     - `app/Modules/Vehicles/Features/Import/Infrastructure/Commands/EngineCommand.php`.
   - Убрать обработку листа свечей из `engine_multi_sheet`.
   - Выровнять колонки import-а с export-ом.
   - Прочитать и сохранить `power_kw_start`, `power_kw_upto` и `fuel_type`, которые сейчас не
     маппятся из основного листа.
   - Применить manager write policy `provider/allow_change_fields`.
   - Не переиспользовать Catalog command напрямую; либо расширить Import Data/Command/Policy, либо
     выделить локальный manager import service в Import feature по архитектурному правилу.
   - Логи: warning для строк, отклоненных из-за защищенных TD-полей, error для неожиданных исключений.
   - Проверка: feature-test на OD create/update, TD locked field error, TD null field allow.

9. Добавить manager import модификаций.
   - Файлы:
     - `app/Modules/Vehicles/Features/Import/Domain/Enums/ExternalImportTypeEnum.php`;
     - `app/Modules/Vehicles/Features/Import/Domain/DTOs/Modification/*`;
     - `app/Modules/Vehicles/Features/Import/Application/Factories/ModificationDataFactory.php`;
     - `app/Modules/Vehicles/Features/Import/Application/Services/Modification/*`;
     - `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/Modification/*`;
     - `app/Modules/Vehicles/Features/Import/Infrastructure/Commands/ModificationCommand.php`;
     - `app/Modules/Vehicles/Features/Import/Application/Factories/ExternalFileImportFactory.php`;
     - `config/rabbit-transport.php`.
   - Добавить `ExternalImportTypeEnum::ModificationCatalog`.
   - Не переиспользовать TecDoc `ModificationCommandImport` как manager import без отдельной policy.
   - По `ms_id` находить vehicle и автоматически рассчитывать `type`.
   - Если `mod_id` пустой, создавать OD-модификацию с отрицательным `mod_id`.
   - Прокинуть `localized_name` и `description_short`; текущий command import DTO их не покрывает.
   - Применить `provider/allow_change_fields`.
   - Не переиспользовать Catalog command напрямую; manager import должен остаться внутри Import
     feature и писать через свой port/command.
   - Логи: warning для строк, отклоненных policy/validation; error для unexpected failures.
   - Проверка: feature-test create OD, update OD, update TD allowed/null field, reject TD locked field, reject mismatched type.

10. Добавить manager import связей `engine_modification`.
   - Файлы:
     - `app/Modules/Vehicles/Features/Import/Domain/Enums/ExternalImportTypeEnum.php`;
     - `app/Modules/Vehicles/Features/Import/Domain/DTOs/EngineModification/*`;
     - `app/Modules/Vehicles/Features/Import/Application/Factories/EngineModificationDataFactory.php`;
     - `app/Modules/Vehicles/Features/Import/Application/Services/EngineModification/*`;
     - `app/Modules/Vehicles/Features/Import/Infrastructure/Imports/EngineModification/*`;
     - `app/Modules/Vehicles/Features/Import/Infrastructure/Commands/EngineModificationCommand.php`;
     - `app/Modules/Vehicles/Features/Import/Application/Factories/ExternalFileImportFactory.php`;
     - `config/rabbit-transport.php`.
   - Добавить `ExternalImportTypeEnum::EngineModifications`.
   - Реализовать синхронизацию по группам `mod_id + type`.
   - Для каждой группы в файле заменить текущий набор `eng_id` на набор из файла.
   - Не трогать группы `mod_id + type`, которых нет в файле.
   - Перед записью валидировать дубли строк и конфликтующие группы внутри файла.
   - Перед записью валидировать отсутствие дублей `mod_id + type` в БД.
   - Ошибки по отсутствующим `mod_id/type` и `eng_id` отдавать в xlsx-отчет.
   - Логи: warning для отклоненных строк/групп; error для unexpected failures.
   - Проверка: feature-test attach, detach через отсутствие строки, no-op для групп вне файла, reject unknown engine/modification.

11. Добавить serialization regression tests для новых queued Excel imports.
    - Файлы:
      - `tests/Unit/Vehicles/Import/*SerializationTest.php`;
      - новые import adapters из задач 8-10.
    - Проверить `serialize($import)` для каждого нового/измененного queued adapter.
    - Проверить, что `registerEvents()` не возвращает closures.
    - Логи: не требуются.
    - Проверка: targeted unit tests.

### Фаза 4. `dan-center`: Filament и Rabbit

12. Обновить UI для двигателей.
   - Файлы:
     - `/home/user/projects/dan-center/app/Filament/Resources/Engines/*`;
     - `/home/user/projects/dan-center/app/Integrations/DanVehicles/Filament/*`;
     - `/home/user/projects/dan-center/config/rabbit-transport.php`.
   - В export/import двигателей оставить только чистый каталог двигателей.
   - Скрыть или удалить из UI загрузку свечей/применяемости двигателей, пока функционал отключен.
   - Локальные `Excel::download(...)` для вынесенных Vehicles-сценариев не использовать.
   - Логи: не добавлять в UI успешный поток; ошибки publish остаются в существующем notifier/handler.
   - Проверка: вручную нажать export/import в Filament и получить уведомление с файлом/отчетом.

13. Добавить UI для импорта/экспорта модификаций.
    - Файлы:
      - `/home/user/projects/dan-center/app/Filament/Resources/VehiclesRest/Actions/*` или отдельный resource/страница модификаций;
      - `/home/user/projects/dan-center/app/Filament/Actions/DanVehiclesUploadXlsxAction.php`, если нужна новая подпись;
      - `/home/user/projects/dan-center/config/rabbit-transport.php`.
    - Добавить кнопку export модификаций.
    - Добавить upload action для `modification_catalog`.
    - Стартовые уведомления: "Запрос на экспорт модификаций отправлен..." / "Запрос на импорт модификаций отправлен...".
    - Result notifications: человекочитаемый текст и кнопка "Открыть файл" для export/error report.
    - Логи: warning/error только если publish/upload не удался.
    - Проверка: вручную export, import валидного файла, import файла с ошибкой protected TD-field.

14. Добавить UI для импорта/экспорта связей.
    - Файлы:
      - `/home/user/projects/dan-center/app/Filament/Resources/VehiclesRest/Actions/*` или отдельная страница связей;
      - `/home/user/projects/dan-center/config/rabbit-transport.php`.
    - Добавить export связей `mod_id + eng_id + type`.
    - Добавить upload action для `engine_modifications`.
    - В UI явно назвать файл: "Связи модификаций и двигателей".
    - Логи: warning/error только если publish/upload не удался.
    - Проверка: экспортировать связи, удалить одну строку, импортировать обратно, проверить что связь удалена.

15. Обновить Rabbit topology в обоих сервисах.
    - Файлы:
      - `config/rabbit-transport.php`;
      - `/home/user/projects/dan-center/config/rabbit-transport.php`.
    - Добавить outbound/inbound mappings для новых event names.
    - Добавить setup bindings:
      - `crm.modifications.export`;
      - `crm.engine-modifications.import`;
      - `crm.engine-modifications.export`.
    - После деплоя/локально выполнить `rabbit-transport:setup` в `dan-vehicles` и `dan-center`.
    - Логи: не требуются; проверка через setup output и Rabbit bindings.
    - Проверка: publish test или ручная отправка каждого нового event.

### Фаза 5. Контрактные тесты и проверка

16. Добавить provider-side tests в `dan-vehicles`.
    - Файлы:
      - `tests/Feature/Vehicles/Import/*`;
      - `tests/Feature/Vehicles/Export/*`;
      - `tests/Feature/Vehicles/Contracts/*`, если такой срез уже используется.
    - Проверить Rabbit payload для новых event names/import/export types.
    - Проверить xlsx headings и round-trip import/export на sample rows.
    - Проверить совместимость result events с `dan-wire-contracts`.
    - Логи: тесты должны проверять behavior, а не наличие debug logs.

17. Добавить consumer-side tests в `dan-center`.
    - Файлы:
      - `/home/user/projects/dan-center/tests/Feature/Integrations/DanVehicles/*`;
      - `/home/user/projects/dan-center/tests/Feature/Filament/*`, если такие тесты есть.
    - Проверить, что Filament отправляет правильные event names, routing keys и `import_type/export_type`.
    - Проверить обработку result notifications.
    - Логи: тесты должны проверять уведомления и payload.

18. Обновить ручные инструкции.
    - Файлы:
      - `plans/proverka.md`;
      - `managment/vehicles-import.md`;
      - `managment/exports.md`;
      - при необходимости `/home/user/projects/dan-center` docs/runbook.
    - Добавить сценарии:
      - экспорт/импорт двигателей;
      - экспорт/импорт модификаций;
      - экспорт/импорт связей;
      - проверка xlsx-отчета ошибок.
    - Логи: не требуются.

## Риски и проверки

- `engine_multi_sheet` сейчас содержит лист свечей. Перед реализацией нужно явно убрать этот лист из менеджерского UI/import/export, чтобы "чистый экспорт импорт" не смешивался с применяемостью.
- Export двигателя сейчас не полностью совпадает с import mapper по порядку колонок. Это надо выровнять до ручного тестирования.
- `MODIFICATIONS_IMPORT_FILE_REQUESTED` в Rabbit config уже есть, но отдельного manager import type для модификаций в `ExternalImportTypeEnum` пока нет.
- `MODIFICATIONS_EXPORT_FILE_REQUESTED` и события для связей нужно добавить в `dan-wire-contracts`, `dan-vehicles` и `dan-center` одновременно.
- `description_short` добавлен в схему отдельно, но до реализации import/export его нужно
  обязательно прокинуть через DTO/Data/REST/Rabbit/Filament.
- Синхронизация связей должна затрагивать только группы `mod_id + type`, присутствующие в файле, иначе частичный импорт может случайно удалить связи вне файла.
- Массовое удаление модификаций через Excel на первом этапе не делать.

## Commit plan

1. `feat(wire-contracts): add modification and engine-link file events`
   - Фаза 1.

2. `feat(vehicles): prepare modification schema and clean exports`
   - Фаза 2.

3. `feat(vehicles): add manager imports for engines modifications and links`
   - Фаза 3.

4. `feat(center): expose engine modification file flows in filament`
   - Фаза 4.

5. `test(dan-vehicles): cover engine modification import export flows`
   - Фаза 5 tests/docs.
