# Рекомендации по рефакторингу — `app/Modules/Vehicles/`

Документ собран по итогам сквозного аудита кода модуля `app/Modules/Vehicles/` (фичи `Export`,
`Import`, `Maintenance`, `Shared`) и соседнего shared-kernel модуля `app/Modules/Templates/`
против конвенций, описанных в `ARCHITECTURE.md`. Каждая находка проверена по факту использования
в коде (`grep` по всему `app/`/`tests/`), а не только по внешнему виду — то, что выглядело
дублированием, но было осознанным архитектурным решением (например, дублирование Eloquent-моделей
по фичам), в список не включено.

Находки сгруппированы по приоритету: **P1** — стоит сделать в первую очередь (реальное
дублирование логики или явный мёртвый код с риском рассинхрона), **P2** — стоит сделать, но не
горит, **P3** — низкий приоритет / на усмотрение.

> **Статус:** актуализировано 2026-07-18. Уже закрытые или снятые как неактуальные пункты удалены
> из списка; ниже оставлены только актуальные рекомендации.

---

## P1 — сделать в первую очередь

Актуальных пунктов P1 не осталось.

---

## P2 — стоит сделать

### 1. Дублирование бизнес-правила «мотоцикл без типа кузова» (Import)

`UpsertVehicleFromSheetService.php:49-57` и `UpsertVehicleFromTdRowService.php:48-55` — буквально
одинаковый блок («TecDoc не даёт тип кузова для мотоциклов → подставить
`CarcaseTypeEnum::MOTORCYCLE`»), в одном ещё висит `TODO: удалить после прогонки импорта и
экспорта`. Перенести дефолтинг в `VehicleDataFactory::make()` (единственное место, которое и так
проверяет валидность `type_carcase`), либо в общий приватный хелпер, вызываемый из обоих
сервисов.

### 2. Разнобой в доступе к Cache/Storage — фасады vs DI (Import)

`ExternalImportCacheService`, `CleanupExternalImportFileService`, `ReportImportResultService`
используют статические фасады `Cache::`/`Storage::` напрямую, тогда как соседний
`EngineModificationReadinessGate` (та же категория задач — cache-состояние импорта) корректно
инъецирует `Illuminate\Contracts\Cache\Repository` через конструктор. По правилу «DI — вариант
А» стоит унифицировать — перейти на конструкторную инъекцию контрактов вместо фасадов, для
единообразия и тестируемости через моки конструктора.

---

## P3 — низкий приоритет / на усмотрение

- **Тестовый helper `readSheets()` дублирован дословно** между
  `tests/Feature/Vehicles/EngineMultiSheetExportTest.php:30-37` и
  `VehicleMultiSheetExportTest.php:44-55`. Вынести в общий trait `Tests\Concerns\ReadsExcelSheets`.
- **`ExportRunCacheService` (Export) и `ExternalImportCacheService` (Import)** реализуют
  одинаковый паттерн «идемпотентность по runId через cache». Не настаиваем на немедленном
  слиянии (в духе принятого в проекте компромисса «дублирование ради независимости фич»), но при
  следующем похожем сценарии — кандидат на `Shared/Infrastructure/Services/CacheIdempotencyService`.
- **Резервные значения export-конфига** (`config('vehicles.export.output.disk', 'local')` и
  аналогичные) остались в `AbstractMultiSheetExport` и `CleanupStaleExportFilesService` — вторые
  аргументы `config(...)` не сработают в штатной работе (конфиг-файл и так задаёт дефолты), но
  создают риск рассинхрона при правке дефолта. Убрать вторые аргументы либо резолвить
  disk/directory/retention в одном месте.
- **`PartSpecificationRowExpander` (Export)** называется обобщённо, но жёстко типизирован под
  `EngineData` (в отличие от `WiperRowExpander`, чьё имя честно отражает специфичность).
  Переименовать в `EnginePartSpecificationRowExpander` либо явно задокументировать
  Engine-specific природу.
- **Два похожих row-mapper'а с разными индексами колонок** — `EngineMainSheetRowMapper` и
  `EngineSheetRowMapper` (`Import/Infrastructure/Imports/Engine/Mappers/`). Разделение оправдано
  (два разных Excel-формата), но хрупко к сдвигу колонок — стоит зафиксировать соответствие
  колонка↔индекс в общей константе/таблице или покрыть построчными тестами с эталонным рядом.
- **Неиспользуемые relation-методы в Import-моделях**: `Feature::values()`, `FeatureValue::feature()`,
  `PartSpecification::vehicle()` (в `Import/Infrastructure/Models/`) — нигде не вызываются.
  Решить: либо реально нужны будущему сценарию (оставить с комментарием), либо удалить.
- **Rendezvous-схема Import (`EngineModificationReadinessGate` + `Subscriber` + cache-флаги +
  `EnginesAndModificationsReady`)** — сама схема корректна и соответствует конвенциям проекта
  (не требует немедленной правки). На будущее: если Excel-адаптеры импорта станут явно
  диспетчериться как `Bus::batch(...)`, состояние «кто уже готов» можно будет держать в родной
  batch-бухгалтерии Laravel вместо самодельных cache-флагов — рассмотреть при следующей ревизии
  Infrastructure-слоя импорта.
- **Документационный пробел, не код**: в ARCHITECTURE.md §3 для `Exports/<Entity>/` не
  зафиксировано (в отличие от `Imports/<Entity>/`), что sheet-адаптеры внутри multi-sheet экспорта
  — «внутренние» и резолвятся через `app()/makeWith()` без отдельного порта. Код так и делает
  (`EngineMultiSheetExport::sheets()`, `VehicleMultiSheetExport::sheets()`) — стоит добавить
  зеркальную фразу в документ, чтобы не выглядело отклонением от правила «порт у каждого класса».
- **Расхождение §0 ARCHITECTURE.md с фактом**: документ утверждает «Import — все 8 сущностей
  (Command пишет во все)», но в `Infrastructure/Commands/` только 6 Command-классов — нет
  `FeatureCommand`/`FeatureValueCommand` (`Feature`/`FeatureValue` в Import только читаются).
  Поправить формулировку в документе или задокументировать источник данных для этих двух сущностей.
