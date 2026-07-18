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

### 2. Дублирование cache-key логики отчёта об ошибках импорта (4 места)

`EngineCrossImport.php:43-50`, `EngineSparkPlugSpecificationImport.php:50-57`,
`EngineMultiSheetImport.php:67-81`, `VehicleMultiSheetImport.php:67-81` — каждый независимо
повторяет `sprintf((string) config('vehicles.import.failures.cache.keys.X'), $runId)`. Вынести в
общий хелпер (метод на `CachesImportFailures` или отдельный
`ImportFailureCacheKeys::forEntity(string $prefix, string $runId): array{cacheKey, lockKey}`).

### 3. Частично избыточная повторная валидация типов (Import)

Формат-конвертер в Infrastructure (`ImportRowValueFormatter::nullableInt/nullableFloat`) уже
бросает исключение и отдаёт типизированный DTO (`?int`/`?float`), но Application-фабрики
(`EngineDataFactory::make()` и аналогичные для Vehicle/Manufacturer/Modification/
EngineModification) повторно валидируют те же поля через `'integer'/'numeric'` правила Laravel
`Validator`, хотя тип уже гарантирован. Оставить в фабриках только то, что реально добавляет
ценность (`required`, `Rule::enum`, бизнес-правила), либо явно задокументировать как осознанный
defense-in-depth.

### 4. Разнобой в доступе к Cache/Storage — фасады vs DI (Import)

`ExternalImportCacheService`, `CleanupExternalImportFileService`, `ReportImportResultService`
используют статические фасады `Cache::`/`Storage::` напрямую, тогда как соседний
`EngineModificationReadinessGate` (та же категория задач — cache-состояние импорта) корректно
инъецирует `Illuminate\Contracts\Cache\Repository` через конструктор. По правилу «DI — вариант
А» стоит унифицировать — перейти на конструкторную инъекцию контрактов вместо фасадов, для
единообразия и тестируемости через моки конструктора.

### 5. Разнобой в именовании констант «поля не для записи» (Import Commands)

`EngineCommand::NON_WRITABLE_FIELDS` vs `ModificationCommand::NON_COLUMN_FIELDS` — одна и та же
концепция под разными именами, остальные Command-классы вообще инлайнят `['id']` без константы.
Унифицировать нейминг/подход во всех Command-классах.

### 6. Избыточная развилка вместо прямого return (Import)

`ExternalImportCacheService::accept()` (`Application/Services/External/ExternalImportCacheService.php:17-24`):
```php
if (Cache::add(...)) { return true; }
return false;
```
эквивалентно `return Cache::add(...);`. Мелкая, но безопасная правка.

### 7. Дублирование в Maintenance: две почти идентичные Artisan-команды

`UpdateModificationYears.php:29-40` и `UpdateVehicleYears.php:29-40` — отличаются только моделью
(`Modification`/`Vehicle`) и колонкой (`year_to`/`generation_year_to`), логика (`get()` + `foreach`
+ `update()` + счётчик) идентична. Вынести в один параметризуемый
`Application/Services/FixYearToService` (модель+колонка как параметры) — заодно исправляет
несоответствие архитектуре (см. п.14).

Заодно там же — N+1: цикл `get()`+`foreach`+`update()` стоит заменить на один массовый
`Model::query()->where(...)->update([...])`, который сам вернёт число затронутых строк.

### 8. Дублирование JSONB-сравнения `details` в Maintenance

`PartSpecificationDeduplicationService.php:110-118` и
`VehicleWiperPartSpecificationSplitService.php:121-130` — оба содержат один и тот же фрагмент
`whereRaw('details = CAST(? AS jsonb)', [json_encode(...)])`. Вынести в общий трейт
(`MatchesPartSpecificationDetails` с методом `whereDetailsEqual($query, array $details)`) — по
собственной политике трейтов проекта («чистое самодостаточное поведение»).

### 9. Двойной пересчёт `splitSpecification()` в Maintenance

`VehicleWiperPartSpecificationSplitService::cleanupEmptySides()` (строка 212) вычисляет
`splitSpecification()` только ради проверки `shouldSplit()` и отбрасывает результат; если проверка
говорит «нужно разбивать», `processSpecification` (строка 68) пересчитывает то же самое заново на
тех же данных. Посчитать один раз и переиспользовать результат.

### 10. Несоответствие архитектуре: Maintenance-команды пишут Eloquent прямо в `handle()`

`UpdateModificationYears`/`UpdateVehicleYears` нарушают собственный паттерн фичи (шпаргалка §6:
«разовый фикс → команда в Presentation + Application/Services»). Соседние
`DeduplicatePartSpecificationsCommand`/`SplitVehicleWiperPartSpecificationsCommand` уже сделаны
правильно (тонкие, делегируют в Service). Исправляется тем же рефакторингом, что и п.11.

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
- **`Vehicle::partSpecifications(): MorphMany` в Maintenance** — не используется ни одним из двух
  Maintenance-сервисов (оба работают через `PartSpecification::query()` напрямую). Не мина (класс
  существует), но неиспользуемая поверхность API — либо убрать, либо оставить с комментарием, как
  уже сделано для убранных связей `Modification::engine()`/`PartSpecification::partable()`.
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
