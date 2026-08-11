# Консолидированный план

Дата актуализации: 2026-08-11.

Этот файл заменяет исторические планы в `plans/`, кроме `enum-generator.md`.

## Принятые решения

- `dan-vehicles` остается headless-сервисом. Пользовательский UI и Filament остаются в `dan-center`.
- Read-сценарии между `dan-center` и `dan-vehicles` идут по REST.
- Write/heavy-сценарии идут через RabbitMQ: import, export, calculation, create, update, delete.
- Межсервисная корреляция в wire-контрактах идет через `operation_id`.
- Projection/read-replica таблицы в `dan-center` не используем.
- Таблица `dan_vehicle_operations` не нужна; состояние операции показывается через Filament notifications.
- `pkmstudio/dan-wire-contracts` является справочником wire DTO/enums для межсервисного общения.
- Внутри домена используются локальные DTO/Data. Wire DTO используются только на REST/Rabbit границе.
- `KitGrouping` не делаем и не держим в backlog.
- Генерация enum из справочников остается отдельной темой в `plans/enum-generator.md`.

## 1. Transport и wire contracts

1. Довести runtime-переход на `pkmstudio/dan-wire-contracts`.
   - `dan-vehicles`: inbound Rabbit handlers должны нормализовать вход через package DTO там, где контракт уже есть.
   - `dan-vehicles`: outbound result events должны публиковаться через package DTO/enums.
   - `dan-center`: publishers, result handlers, REST clients и action-классы должны использовать package DTO/enums на границе.
   - Не протаскивать package DTO глубоко в доменные use cases; на входе маппить в локальные DTO/Data.

2. Проверить полноту пакета `dan-wire-contracts`.
   - Vehicles: import/export, catalog mutations, CRM read DTO.
   - Warehouse: import/export, catalog mutations, CRM read DTO для nomenclatures и следующих сущностей.
   - Applicability: import/export/calculation request/result DTO.
   - Shared result DTO: import completed, file exported, catalog mutation completed.

3. Добавить контрактные тесты на wire payload.
   - Тестировать `toArray()/fromArray()` package DTO.
   - Тестировать реальные sample payload из `dan-center` против handlers `dan-vehicles`.
   - Тестировать реальные result payload из `dan-vehicles` против consumers `dan-center`.

4. Закрыть вопрос REST-auth.
   - Выбрать и внедрить один вариант: внутренний API key или строго закрытая docker/network зона.
   - Если выбираем API key, добавить обязательную проверку на `/api/v1/crm/*`.

5. HMAC для Rabbit включать только после полной готовности обоих сервисов к одинаковому envelope.

6. Повторить `rabbit-transport:setup` в stage/prod после деплоя новых bindings.
   - Проверить `crm.* -> vehicles.inbox`.
   - Проверить `vehicles.#`, `warehouse.#`, `applicability.# -> crm.inbox`.
   - Отдельно проверить DLQ/poison behavior.

## 2. `dan-center`: Filament и интеграционный слой

1. Закрепить постоянный worker/Horizon для `crm.inbox`.
   - Уведомления Filament не должны зависеть от ручного запуска `php artisan horizon`.

2. Провести инвентаризацию старых зависимостей.
   - Найти все `App\Models\Vehicles`.
   - Найти все `App\Models\Warehouse`.
   - Разделить на CRM-owned данные и вынесенные домены.
   - Для вынесенных доменов составить замену: REST read endpoint или Rabbit command.

3. Отрефакторить Filament.
   - Не держать сборку Rabbit payload, upload в S3, REST mapping и orchestration notifications прямо в table/page classes.
   - Вынести это в integration/application services и action-классы.
   - Filament должен остаться UI-слоем: form/table/action декларации плюс вызов готовых сервисов.

4. Довести REST-backed resources и отключить старые Eloquent resources.
   - Vehicles: оставить финальный route `/vehicles`, без `Rest` в UI.
   - Nomenclatures: проверить parity со старым Eloquent ресурсом и отключить старый.
   - Engines: сделать REST-backed list/detail/create/edit/delete.
   - Manufacturers: сделать REST-backed list/detail/create/edit/delete.
   - Modifications: сделать REST-backed list/detail/create/edit/delete.
   - PartSpecifications: сделать явные actions/sections для CRUD.
   - Warehouse Brands: сделать REST/Rabbit flow.
   - Warehouse Kits: сделать REST/Rabbit flow, включая состав набора.
   - Warehouse PackDimensions: сделать REST/Rabbit flow.
   - Applicability: решить UI-сценарии read/import/export/calculation/manual attach.

5. Довести nested-сценарии.
   - PartSpecification CRUD внутри Vehicle/Engine edit.
   - Modification links и engine links.
   - Kit composition update.
   - Applicability manual attach/sync, если сохраняем ручной сценарий.

6. Уведомления привести к человекочитаемому виду для всех операций.
   - Старт: "Запрос отправлен", показывать `operation_id`.
   - Успех export/import report: кнопка "Открыть файл", если файл есть.
   - Mutations: "Запрос на создание/редактирование/удаление ... завершен".
   - Ошибки: показывать понятную сущность, количество ошибок и ссылку на xlsx-отчет, если он сформирован.

7. Удалить старые прямые import/export/write вызовы для вынесенных доменов.
   - `Excel::download(new ...)`.
   - Локальные `UploadXlsxAction->importerClass(...)`.
   - Прямые writes в старые Eloquent модели Vehicles/Warehouse.

8. Если `dan_vehicle_operations` была создана в stage/prod, удалить ее вручную или отдельной cleanup-миграцией.

## 3. `dan-vehicles`: REST read API

1. Vehicles CRM API довести до parity со старым Filament.
   - List/search/filter/sort/pagination.
   - Detail snapshot.
   - Options endpoints для features, feature values, detail templates, manufacturers.
   - Данные для старой формы: vehicle fields, part specifications, modifications, engines.

2. Warehouse Nomenclature CRM API довести до parity.
   - List/search/filter/sort/pagination.
   - Detail snapshot.
   - Options endpoints для brands, types/templates.
   - Проверить, что форма получает все данные с REST, а не из локальных таблиц `dan-center`.

3. Добавить REST read API для оставшихся вынесенных сущностей.
   - Engines.
   - Manufacturers.
   - Modifications.
   - PartSpecifications.
   - Warehouse Brands.
   - Warehouse Kits.
   - Warehouse PackDimensions.
   - Applicability read endpoints.

4. Добавить OpenAPI или контрактные feature-тесты на response shape.
   - Минимум для endpoints, которые использует Filament.
   - Фиксировать поля, сортировки, фильтры и формат ошибок.

## 4. `dan-vehicles`: Rabbit write/heavy flow

1. Проверить все inbound commands.
   - Vehicles import/export.
   - Engines import/export.
   - Vehicles catalog mutations: vehicle, manufacturer, engine, modification, part specification.
   - Warehouse import/export.
   - Warehouse catalog mutations: brand, nomenclature, pack dimension, kit.
   - Applicability import/export/calculation.

2. Проверить result events.
   - `VEHICLES_IMPORT_COMPLETED`.
   - `VEHICLES_FILE_EXPORTED`.
   - `VEHICLES_CATALOG_MUTATION_COMPLETED`.
   - `WAREHOUSE_IMPORT_COMPLETED`.
   - `WAREHOUSE_FILE_EXPORTED`.
   - `WAREHOUSE_CATALOG_MUTATION_COMPLETED`.
   - `APPLICABILITY_IMPORT_COMPLETED`.
   - `APPLICABILITY_FILE_EXPORTED`.
   - `APPLICABILITY_CALCULATION_COMPLETED`.

3. Выровнять статусы.
   - Для UI `completed_with_errors` и `completed_with_failures` не разводить без отдельного смысла.
   - В wire-контрактах оставить понятный общий статус.

4. Проверить idempotency по `operation_id`.
   - Дубликат команды не должен запускать второй импорт/export/mutation.
   - Ошибочный запуск должен корректно освобождать ключ, если сообщение нужно повторить.

5. Доработать создание `PartSpecification`.
   - Убрать требование пользовательского ввода внутреннего `id`, если оно еще осталось в contract/UI.
   - Новый id должен генерироваться на стороне `dan-vehicles`.

6. Добавить granular change events там, где они нужны CRM-specific reactions.
   - Vehicles: created/updated/deleted по vehicle, engine, modification, part specification, manufacturer.
   - Warehouse: created/updated/deleted по brand, nomenclature, kit, pack dimension.
   - `dan-vehicles` не должен знать про MpSale; он только публикует факты.

## 5. Applicability

1. Довести расчет применяемости.
   - Wiper расчет уже есть, проверить полноту и производительность.
   - Spark plugs auto-calculation пока не реализован в factory, добавить отдельным алгоритмом.
   - Остальные типы оставить через manual/import workflow, пока нет правил.

2. Довести export.
   - Vehicle kit applicability export уже есть.
   - Engine kit applicability export отсутствует или не подключен полностью; реализовать, если нужен старый сценарий.

3. Довести import.
   - Проверить ручной XLSX import применяемости.
   - Проверить xlsx-отчет ошибок и result notification.

4. Event-autorecalc оставить отложенным до проверки производительности.
   - Kit created/updated/deleted.
   - Nomenclature updated/deleted.
   - Vehicle/modification/engine/part specification updated/deleted.
   - На первом этапе запуск через command/Rabbit calculation request достаточен.

5. Добавить REST read API для consumers.
   - Минимум: получить применяемость по vehicle/modification/engine.
   - Решить, отдавать только ids комплектов или отдельные enriched read DTO для CRM.

6. Добавить тесты.
   - Wiper расчет.
   - Spark plugs расчет после реализации.
   - Manual import negative `ms_id`.
   - Export rows.
   - Result events.

## 6. Warehouse

1. Робастность Excel-импорта.
   - Решить, оставляем fail-fast для неожиданных багов или ловим `Throwable` на row-level.
   - Если расширяем обработку, писать ошибочную строку в failures и продолжать импорт.

2. Проверить Nomenclature import rules.
   - Обязательность полей должна соответствовать шаблонам.
   - Для wiper: обязательна `steering`; длина должна быть хотя бы одна из driver/rear, passenger не обязательна.
   - Для brake pads, spark plugs, oil filter, air filter, cabin filter: обязательные поля и размеры по текущим решениям.
   - Для wiper adapters: поле `construction` убрано, остальные обязательные.

3. MoySklad.
   - Typed DTO на границе уже есть; отдельно проверить, что Application не работает с raw payload внешнего API.
   - Проверить jobs/listeners после Rabbit catalog mutations.

4. Типы товара и `TypeTemplateResolver`.
   - Пока остаются текущие resolver-ы.
   - Если нужен стабильный enum/dictionary механизм, делать по отдельному плану `plans/enum-generator.md`.

## 7. Templates

1. Сделать ошибки Templates явными.
   - Заменить сырой `Enum::from(...)` в public client на безопасную конвертацию.
   - Добавить доменные исключения для неизвестного template, некорректной ячейки и неизвестного enum value.

2. Сделать строгий parsing/export validation.
   - Не превращать нечисловые значения в `0` через `(int)`/`(float)`.
   - Не терять неизвестные enum names при export.

3. Убрать inline-вычисления в больших presenter/factory вызовах.
   - Вынести `Data::from($details)` в именованные переменные.
   - Сложные `new`/fallback выражения не держать внутри аргументов вызовов.

4. Разгрузить большие selectors.
   - Рассмотреть registry/map `template => builder/presenter`.
   - `referenceOptions` перенести ближе к конкретным presenter-ам, если это снизит риск забыть новый шаблон.

5. Добить unit-тесты nomenclature-ветки.
   - Factory tests по основным шаблонам уже есть.
   - Добавить/проверить presenter tests для headings, cells, reference options и ошибок.

## 8. Vehicles refactor

1. Убрать дублирование правила "мотоцикл без типа кузова".
   - Сейчас логика есть в sheet/TecDoc upsert flow.
   - Перенести defaulting в одну factory/policy точку.

2. Проверить config fallbacks в export.
   - Убрать вторые аргументы `config(...)`, если дефолты уже есть в config-файле.
   - Не держать разные дефолты в разных местах.

3. Уточнить нейминг `PartSpecificationRowExpander`.
   - Если expander только для engine spark plug sheet, переименовать в более явное имя.

4. Зафиксировать Excel column indexes для engine row mappers.
   - Добавить тесты с эталонными строками или константы колонок.

5. Удалить или явно задокументировать неиспользуемые relation-методы в Import-моделях.
   - `Feature::values()`.
   - `FeatureValue::feature()`.
   - `PartSpecification::vehicle()`.

## 9. Документация и cleanup

1. Обновить `ARCHITECTURE.md`.
   - Убрать ссылки на старые `plan.md`/`refactor-ms.md`, если файлов уже нет.
   - Уточнить, что для межсервисных Rabbit/REST контрактов используется `operation_id`.
   - Оставить `runId` только там, где это внутренний контекст импорта, если он действительно еще используется.

2. Обновить `.ai-factory/ARCHITECTURE.md` теми же правилами, если нужно.

3. Обновить runbook.
   - Как выполнить Rabbit setup.
   - Как проверить S3.
   - Как запустить workers.
   - Как локально проверить Filament flow через `dan-center`.

4. После переключения сущностей удалить старые deprecated планы/документы и не держать параллельные источники правды.
