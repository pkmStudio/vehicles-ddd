# План: Warehouse — актуальный остаток

> Статус: актуализировано 2026-07-18.
> Реализованные разделы очищены из плана; ниже только открытые задачи и сознательно отложенные
> внешние работы.

## Что уже не держим в плане

Warehouse MVP реализован и не требует повторного планирования в этом документе:

- `Templates` вынесен в `app/Modules/Templates/*` и расширен под Warehouse-номенклатуру.
- Миграции Warehouse лежат в
  `app/Modules/Warehouse/Shared/Infrastructure/Database/Migrations`.
- Реализованы `Import`, `Export`, `Packaging`, `KitProperties`, `WiperAdapterAudit`,
  `Maintenance`, `Catalog`, `MoySklad`.
- Добавлены сидеры Warehouse-справочников и упаковок.

## Осталось

1. **`KitGrouping`**

   Автогруппировка остатков номенклатуры в кандидаты на новые наборы.

   Текущий статус: не начато, папки `app/Modules/Warehouse/Features/KitGrouping` нет.

   Нужно отдельно спроектировать границу фичи: входные данные, алгоритмы группировки по типам
   номенклатуры, формат кандидатов, способ запуска и место сохранения результата.

2. **Робастность Excel-импорта**

   Решить, должны ли row-level Excel-импорты гасить более широкий набор ошибок и продолжать
   обработку остальных строк.

   Текущий статус:

   - `NomenclatureImport` ловит `InvalidArgumentException|RuntimeException`.
   - `PackDimensionImport` ловит только `InvalidArgumentException`.
   - `KitImport` ловит только `InvalidArgumentException`.

   Открытое решение: оставить fail-fast для неожиданных багов или расширить обработку до
   `Throwable` с логированием и записью строки в failures.

3. **RabbitMQ setup в целевом окружении**

   Код и конфиг Warehouse-событий уже есть, но состояние брокера внешнее.

   Нужно выполнить `rabbit-transport:setup` в целевом окружении и проверить, что exchange,
   очереди и bindings для Warehouse import/export/catalog реально объявлены и нет `NO_ROUTE`.

## Отложено вне этого плана

- **`Applicability`** — отдельный будущий домен: `kit_oem_numbers`, `kit_kit_group`,
  `kit_groups`, `kit_applicabilitables`, применимость Kit к Vehicles/Engines.
- **MpCard-инвалидация** — кросс-доменная задача, см. `plan-new.md`.
- **`larastan`/`phpstan` + CI** — отдельная независимая задача, см. `plan-new.md`.
- **Filament-админка** — не нужна, сервис остается headless.
