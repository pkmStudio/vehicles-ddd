# План переноса: разделение спецификаций дворников по сторонам (front/back)

> Источник — два коммита в `dan-center` (старый монолит):
> - `1d48be9ecb97ee7cb95f735ec47d7a126f882d52` — основная реализация
> - `1cb63826b33e626dfc4498f5ce959bf0260cc47a` — правки применяемости
>
> Цель — перенести в `dan-vehicles` то, что относится к домену Vehicles (шаблон, импорт, экспорт, доменная логика), не таща Warehouse/MpSale/Filament.

## Суть фичи

Раньше дворники одного авто хранились как **одна** `PartSpecification` с `details = {front:{...}, back:{...}}`.
Стало — **одна `PartSpecification` на сторону**: запись содержит либо только `{front:...}`, либо только `{back:...}`. Сторона определяется по наличию корневого JSON-ключа.

Под это перестроены: шаблон полей, импорт (split по сторонам), экспорт (обратная склейка front×back), новый доменный сервис разбора сторон, команды миграции данных (latent), обсервер инвалидации MP-карточек (latent).

---

## Архитектурные решения (зафиксировать до начала)

1. **Сервис разбора сторон → первый `Domain/Services/`.** Логика `detectSide`/`sideData`/`normalizeAdapters`/`splitSpecification`/`mergeForExport` — это чистое бизнес-правило «как устроена спецификация дворника» над массивом `details`, без инфраструктуры. Кладём в `app/Vehicles/Domain/Services/`.
2. **Query-часть (`jsonb_exists`) → за репозиторий-порт.** Поиск существующей записи «по стороне» (PostgreSQL `jsonb_exists`) — это чтение, идёт через `PartSpecificationRepositoryInterface` (новый метод), реализация в Infrastructure.
3. **Меняется ключ upsert дворников.** Сейчас upsert по `partable + template + feature_value_id`. Станет — **по стороне** (front/back игнорируя `feature_value_id`). Это затронет контракт `PartSpecificationCommandInterface` и/или use-case. Самый чувствительный пункт — продумать, чтобы не сломать upsert других шаблонов (свечи и т.п. остаются по старому ключу).
4. **UI-логика Filament не переносится.** `position`-переключатель, `live`, `afterStateHydrated`, `dehydrateStateUsing`, `ConditionalObjectField::toFilamentForm()` — это поведение формы. dan-vehicles headless: переносим только `toArray()`-контракт данных.

---

## Что переносим СЕЙЧАС (по тирам, снизу вверх)

### Тир 1 — field-templates (data-контракт пакета) · сложность: низкая
Фундамент: без новых полей шаблон и импорт/экспорт не соберутся.

- `packages/field-templates/src/CommonFields.php`:
  - `rangeInteger()` — диапазон целых (для длин щёток вместо float `range()`).
  - `adapterTypeFrontForVehicle()` / `adapterTypeRearForVehicle()` — адаптер с `max:1` / `maxItems:1` (один код на сторону), структура данных остаётся массивом.
- `packages/field-templates/src/Fields/SelectField.php`:
  - добавить `?int $maxItems` + ключ `'max_items'` в `toArray()`.
- `packages/field-templates/src/Fields/ConditionalObjectField.php` (**новый**):
  - обёртка над `ObjectField` + `dependency`/`dependencyValue`; в `toArray()` отдаёт `dependency` и `dependency_value`. Аналог уже существующего `ConditionalSelectField`. **Без** Filament-метода.

Источник: `app/Lists/FieldTemplates/CommonFields.php`, `app/Lists/FieldTypes/{ConditionalObjectField,SelectField}.php` (dan-center).

### Тир 2 — харднинг билдеров деталей · сложность: тривиально
Микро-защита от мусора в конфиге (1:1 перенос):
- `app/Vehicles/Infrastructure/Imports/Support/DetailsBuilder.php`
- `app/Vehicles/Infrastructure/Exports/Support/ExportDetailsBuilder.php`

Изменение: `isset($cfg['children'])` → `isset(...) && is_array($cfg['children'])`; `isset($cfg['options_source'])` → `is_array($cfg['options_source'] ?? null)`.
Источник: `app/Traits/Warehouse/{BuildDetails,BuildExportDetails}.php`.

### Тир 3 — доменный сервис разбора сторон · сложность: средняя
**Новый** `app/Vehicles/Domain/Services/WiperSpecificationSideService` (имя уточнить), чистая логика:
- `detectSide(array $details): ?string` — по корневым ключам `front`/`back`.
- `sideData(array $details, string $side): array`.
- `normalizeAdapters(...)` — ≤1 код адаптера на сторону.
- `sanitizeDetailsForSide(...)`.
- `splitSpecification(...)` — legacy `{front,back}` → массив односторонних.
- `mergeForExport(?front, ?back): array` — обратно в `{front, back}` для excel.

Query-методы (`buildByVehicleTemplateAndSideQuery` / `findByVehicleTemplateAndSide` через `jsonb_exists`) — **НЕ в сервис**, а в `PartSpecificationRepositoryInterface` (порт) + реализация в Infrastructure.
Источник: `app/Services/Vehicles/PartSpecifications/WiperVehicleSpecificationDetailsService.php` (на Warehouse/Kit НЕ завязан — только `PartSpecification`/`Vehicle`).
Покрыть unit-тестами (чистый сервис — легко).

### Тир 4 — WiperTemplate (только data-аспекты) · сложность: средняя
`app/Vehicles/Domain/Templates/Vehicle/Templates/WiperTemplate.php`:
- `range` → `rangeInteger` (целые длины).
- адаптеры → `adapterType*ForVehicle()` (max:1).
- **НЕ переносим**: `position`-переключатель, condition-видимость, `live`/hydrate (Filament-UI).

### Тир 5 — импорт (split по сторонам) · сложность: средняя-высокая
- Расширить `app/Vehicles/Application/Import/UseCases/Vehicle/UpsertVehicleWiperSpecUseCase` (или новый use-case): из `details` выделить `front`/`back`, для каждой стороны с полезными данными — нормализация адаптеров → upsert по стороне.
- Поиск кандидата по стороне — новый метод репозитория (`jsonb_exists`).
- Контракт `PartSpecificationCommandInterface` — метод upsert «по стороне» (или параметр side), не ломая upsert других шаблонов.
- Адаптер `Infrastructure/Imports/Vehicle/Sheets/VehicleWipersSheetImport` остаётся тонким (механика), вызывает use-case.

### Тир 6 — экспорт (обратная склейка) · сложность: средняя-высокая
- Отдельный wiper-expander (рядом с `Exports/Support/PartSpecificationRowExpander` либо расширение): группировка спецификаций по шаблону → односторонние front / back / legacy «обе» → декартово произведение front×back в строки → `mergeForExport()` (сервис из тира 3).
- `feature_value`/`name`/`text` берутся «front, иначе back» (+лог при конфликте).
- Адаптер `Exports/Vehicle/Sheets/VehicleWipersSheetExport` использует expander.

---

## Latent / вне scope (НЕ переносим сейчас)

- **Команды миграции данных** `SplitVehicleWiperPartSpecificationsCommand`, `DeduplicatePartSpecificationsCommand` — целиком завязаны на `KitApplicabilitable`/`TrashKitApplicability`/`Kit`/`WiperApplicabilityResolver` (Warehouse+MpSale). Это разовая миграция существующих данных — **отрабатывает в dan-center**.
- **Applicability**: `WiperApplicabilityResolver`, `WiperApplicabilityImportChecker`, `WiperVehicleFinder`, `ResolvesVehicleApplicability`, `MpCardRelinkResultService` — домены Warehouse/MpSale (не перенесены).
- **MpCard-инвалидация**: `PartSpecificationObserver` + `InvalidateMpCardsByPartSpecificationJob` — технически переносимы по образцу существующего `VehicleObserver`/`InvalidateMpCardsByVehicleJob`, но завязаны на MP-карточки. **Отложить** вместе с доменом MpSale.
- **Filament**: `VehicleForm.php`, `MpCardForm.php`, все `toFilamentForm`/`live`/`integer()`/hydrate — headless-сервис, не переносим.
- **`fullVehicleNameWithSide`** (атрибут модели) — нужен базовый `fullVehicleName`, которого в dan-vehicles нет; отображение, низкий приоритет.

---

## Порядок и риски

Порядок: **1 → 2 → 3 → 4 → 5 → 6** (импорт/экспорт зависят от фундамента 1–3).

Главные риски:
- **Ключ upsert по стороне** (тир 5) — не сломать upsert не-дворниковых шаблонов; держать изменение узким (отдельный путь для wiper).
- **`jsonb_exists`** — PostgreSQL-специфично (как и в монолите); в тестах БД sqlite — query-методы тестировать на Postgres либо мокать порт.
- **Сторона как контракт данных** — `front`/`back` корневые ключи должны быть согласованы между импортом, экспортом, сервисом и шаблоном (одни константы/enum).

## Прогресс
- [x] Тир 1 — field-templates (`SelectField.maxItems`, `CommonFields::rangeInteger/adapterType*ForVehicle`, новый `ConditionalObjectField`)
- [x] Тир 2 — харднинг билдеров (`is_array` для children/options_source в Details/ExportDetailsBuilder)
- [x] Тир 3 — доменный сервис `Domain/Services/WiperSpecificationService` (+ порт `PartSpecificationRepository::firstByVehicleTemplateAndSide` через `jsonb_exists`)
- [x] Тир 4 — `WiperTemplate` (rangeInteger, адаптеры max:1, position-переключатель + condition front/back, position вырезается в getArrayTemplate)
- [x] Тир 5 — импорт: `UpsertVehicleWiperSpecUseCase` split по сторонам + per-side create/update
- [x] Тир 6 — экспорт: `WiperRowExpander` (front×back) + merge в `VehicleWipersSheetExport`

**Покрыто тестами:** `WiperSpecificationService` (detect/split/normalize/merge), `UpsertVehicleWiperSpecUseCase` (per-side create/update, feature_value). Полный сьют — 31 passed.

## ⚠️ Требует проверки на PostgreSQL
`PartSpecificationRepository::firstByVehicleTemplateAndSide` использует `jsonb_exists` — работает только на Postgres (в unit-тестах порт мокается, sqlite его не выполнит). Перед продом прогнать импорт дворников на Postgres-БД и проверить, что:
1. front/back пишутся раздельными записями (один корневой ключ);
2. повторный импорт обновляет нужную сторону (а не плодит дубли);
3. экспорт склеивает обратно в один ряд.

## Осталось latent (когда появятся домены / Filament)
Команды миграции (`Split…`/`Deduplicate…`) — в dan-center; applicability/MpCard-обсервер; Filament-формы; `fullVehicleNameWithSide`.
