# RESEARCH — открытые вопросы и нереализованное

> Закрытые решения и правила слоёв переехали в **`ARCHITECTURE.md`**.
> Здесь — только то, что ещё **не сделано** или **не решено**.

## Открытые вопросы

### Трейты — РЕШЕНО
Все переведены, кроме двух — оба осознанно оставлены трейтами:
- **`CachesImportFailures`** (6 импортов) — **оставлен трейтом** (решение принято). Пробовали вынести в сервис `ImportFailureCache`, но эти классы — maatwebsite-импорты, которые в `sheets()` строят дочерние листы с рантайм-скалярами (`userId`/`cacheKey`) через `makeWith`/`new`; инъекция сервиса только размножает `makeWith` в методах. Трейт здесь легитимен: самодостаточное состояние (`$cacheKey`/`$lockKey`) + поведение, без скрытого контракта на хост сверх собственных свойств.
- **`EnumHelperTrait`** — оставляем трейтом (enum не наследует класс).

### События: Domain vs Application/Integration
Пока все события в `Domain/Events` (решение владельца). Возможное будущее деление по границе:
- **Domain events** — бизнес-факты, in-process → `Domain/Events`.
- **Application/process events** — жизненный цикл сценария → `Application`.
- **Integration events** — контракт для других сервисов (сериализуются, шина, версионируемый shape) → `Infrastructure/Messaging`.
- Паттерн: доменное событие не публикуем наружу как есть — тонкий Application-листенер транслирует его в integration event (publisher). Решить, когда появится второй потребитель.

### Filament-рендер в пакете `dan/field-templates` — РЕШЕНО
Разделили ядро и рендер. Ядро (`Fields/*`, `AbstractTemplate`) — чистый PHP: только описание полей + `toArray()`/`getArrayTemplate()`, **ни одного `use Filament`**. Рендер вынесен в **один опциональный класс-адаптер** `Dan\FieldTemplates\Rendering\FilamentTemplateRenderer`, который потребляет `getArrayTemplate()` (контракт = структура массива, не объекты полей). Адаптер «спящий»: определяется/инстанцируется без установленного Filament (use — алиасы, типы в сигнатурах резолвятся лениво), Filament нужен только при вызове `render()`. Headless-сервисы (dan-vehicles) пакет используют, рендерер не трогают. Filament — снова `suggest`, но уже только для этого класса, не для ядра.

## Application × модели (правило A) — добить вынос Eloquent

Решено: правило **A** (Application читает модели, но персистентность только через порты; см. `ARCHITECTURE.md`). Эталон `UpsertVehicleFromSheetUseCase` вычищен. Остаточный инлайн-Eloquent в Application:

- [x] **Активные (vehicles/engine) — ВЫЧИЩЕНО:**
  - `UpsertVehicleFromSheetUseCase` → порты `VehicleRepository::{minMsId,firstByMsId}`, `ManufacturerRepository::minMfaId`, `ManufacturerCommand::{firstOrCreateByName,firstOrCreateByMfaId}`.
  - `EngineMainSheetImport` → `EngineCommand::updateEditableByEngId(engId, attrs)`.
  - `EngineCrossImport` → `EngineRepository::firstByCodeEngine` + `EngineCommand::setGroupId`.
- [ ] **Latent (ждут непортированные домены Warehouse/MpSale):** `Imports/EngineSparkPlugSpecificationImport` (Vehicle/Modification + Warehouse Kit), `Exports/{Vehicle,Engine}/*KitApplicabilityExport` (Kit/PartSpec), `Jobs/{Vehicle,Engine}/InvalidateMpCards*` (MpCard). Чистим, когда появятся эти домены.
- [ ] **`User::find(...)`** в multi-sheet/cross импортах (сборка payload события завершения) — кросс-доменное чтение User; вынести за порт или оставить как event-assembly (решить).

## Нереализованное (TODO)

- [ ] **Трейты — добить** при необходимости: `CachesImportFailures` → `ImportFailureCache` (если решим уходить от трейта).
- [ ] **Latent jobs** `InvalidateMpCardsByEngineJob` / `…ByVehicleJob` — завязаны на `MpCard`, `kit_applicabilitables`, `ResolveDirtyCardsJob` из доменов **MpSale/Applicabilities** (ещё не перенесены). Логику вытащить, когда появятся эти домены.
- [ ] **Outbound-уведомления** наружу: события `IMPORT_SUCCEEDED` / `IMPORT_FAILED` сервису с Filament (см. `OutboundEventsEnum`, листенер `ExportImportErrors`). UI-нотификации → событие в Filament-сервис; файлы ошибок → S3 + сообщение.
- [ ] **Домен Warehouse** (при заведении): своя копия generic-классов, свои `WarehouseServiceProvider` + `EventServiceProvider` + `Messaging` (очередь `warehouse.inbox`), свой enum шаблонов деталей.
- [ ] **`types` (master-data Warehouse)** — не enum и не общая БД: локальная таблица в каждом домене, синхронизируемая событиями (владелец публикует изменения в `application.events`). Для Vehicles неактуально.
- [ ] **Миграции** — ещё не запускались (по договорённости).
