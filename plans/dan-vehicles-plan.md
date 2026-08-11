# План `dan-vehicles`

Дата актуализации: 2026-08-11.

## Принятые решения

- `dan-vehicles` остается headless-сервисом. Пользовательский UI и Filament остаются в `dan-center`.
- Read-сценарии между `dan-center` и `dan-vehicles` идут по REST.
- Write/heavy-сценарии идут через RabbitMQ: import, export, calculation, create, update, delete.
- Межсервисная корреляция идет через `operation_id`.
- `pkmstudio/dan-wire-contracts` является справочником wire DTO/enums на REST/Rabbit границе.
- Внутри домена используются локальные DTO/Data. Wire DTO используются только на REST/Rabbit границе.
- `KitGrouping` не делаем и не держим в backlog.
- Генерация enum из справочников остается отдельной темой в `plans/enum-generator.md`.
- `VehicleCrmReadQueryFactory` и `NomenclatureCrmReadQueryFactory` остаются feature-local. Общий
  parser для `page/per_page/search/sort/filter` не вводим, чтобы не связывать независимые read API.

## Порядок реализации

1. Сначала выполнить архитектурную чистку из разделов 8, 9 и 10.
   - Убрать SQL из public clients.
   - Развести HTTP controllers, application clients, use cases и repositories.
   - Зафиксировать architecture gate после устранения известных нарушений.

2. После этого расширять REST/Rabbit функциональность из разделов 1-4.
   - Новые endpoints и handlers должны сразу идти по целевой схеме.
   - Новые тесты писать как feature/business scenario tests, а unit-тесты оставлять только для
     чистых правил и узких regression gates.

## 1. Transport и wire contracts

1. Довести runtime-переход на `pkmstudio/dan-wire-contracts`.
   - Inbound Rabbit handlers должны нормализовать вход через package DTO там, где контракт уже есть.
   - Outbound result events должны публиковаться через package DTO/enums.
   - REST read endpoints должны отдавать согласованный wire shape.
   - Package DTO не протаскивать глубоко в доменные use cases; на входе маппить в локальные DTO/Data.

2. Проверить полноту пакета `dan-wire-contracts`.
   - Vehicles: import/export, catalog mutations, CRM read DTO.
   - Warehouse: import/export, catalog mutations, CRM read DTO для nomenclatures и следующих сущностей.
   - Applicability: import/export/calculation request/result DTO.
   - Shared result DTO: import completed, file exported, catalog mutation completed.

3. Добавить producer/provider контрактные тесты.
   - Тестировать `toArray()/fromArray()` package DTO.
   - Тестировать реальные sample payload из `dan-center` против handlers `dan-vehicles`.
   - Тестировать реальные result payload из `dan-vehicles`.
   - Тестировать REST response shape для endpoints, которые использует `dan-center`.

4. Закрыть REST-auth на стороне API через middleware из раздела 8.
   - Не держать auth guard внутри controllers.
   - API key проверять на `/api/v1/crm/*` и catalog endpoints.

5. HMAC для Rabbit включать только после полной готовности обоих сервисов к одинаковому envelope.

6. Повторить `rabbit-transport:setup` в stage/prod после деплоя новых bindings.
   - Проверить `crm.* -> vehicles.inbox`.
   - Проверить `vehicles.#`, `warehouse.#`, `applicability.# -> crm.inbox`.
   - Отдельно проверить DLQ/poison behavior.

## 2. REST read API

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

## 3. Rabbit write/heavy flow

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

## 4. Applicability

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

7. [x] Убрать прямую зависимость Applicability Export от Vehicles enum.
   - `Applicability/Features/Export/Application/Services/VehicleKitApplicabilityExportService`
     сейчас использует `Vehicles/Shared/Domain/Enums/Vehicle/CarcaseTypeEnum` для reference rows.
   - Если значения нужны как внешний справочник export-файла, получать их через локальный client
     port или отдельный export reference provider.
   - Application `Applicability` не должен напрямую импортировать `Vehicles` даже из `Shared`,
     кроме случаев, явно оформленных как локальный client adapter.

## 5. Warehouse

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
   - Feature-local resolver-ы остаются в своих фичах.
   - Общий resolver не вводить без отдельной необходимости: небольшое дублирование здесь дешевле,
     чем лишняя связность независимых фич.
   - Если нужен стабильный enum/dictionary механизм, делать по отдельному плану `plans/enum-generator.md`.
   - При разрезании `WarehouseApplicabilityClient` не оставлять в public client собственную копию
     таблицы `type -> NomenclatureDetailTemplateEnum`; public client должен получить готовые DTO от
     owner read layer.

5. Убрать leakage exception-классов между фичами.
   - `Warehouse/Catalog/Application/UseCases/Kit/CreateKitUseCase` не должен импортировать
     `Warehouse/KitProperties/Domain/Exceptions/KitCompositionException`.
   - `Warehouse/Import/Application/Services/Kit/ImportKitFromRowService` не должен импортировать
     `Warehouse/KitProperties/Domain/Exceptions/KitCompositionException`.
   - `Warehouse/*/Infrastructure/Clients/KitPropertiesClient` должен ловить exception владельца
     `KitProperties` и переводить его в локальный exception/result DTO фичи-потребителя.
   - Application фичи-потребителя работает только со своим `Domain/Contracts/Clients` и своими
     `Domain/Exceptions`.

## 6. Templates

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

## 7. Vehicles refactor

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

## 8. Границы HTTP/read adapters и public clients

Целевое решение: `Controller` и `Client` считаются разными входными adapter-ами к application
сценариям. HTTP controller отвечает за REST transport, request parsing, auth boundary и response
shape. Public client отвечает за синхронный контракт конкретного потребителя: CRM, catalog/store,
Applicability и т.п. Не делаем один глобальный client на весь модуль.

Архитектурная цель становится жестче: обычные `Application` и public `Client` не ходят в БД, не
знают Eloquent/SQL и не ловят exception-классы чужих фич. Исключение `Maintenance` пока оставляем
как есть и не трогаем до отдельного изучения.

Предпочтительная форма для внешних read API:

```text
Presentation/Http/Controllers/*Controller
  -> Domain/Contracts/Clients/*CrmClientInterface или *CatalogClientInterface
  -> Application/Clients/*CrmClient или *CatalogClient
  -> Application/UseCases или query services
  -> Domain/Contracts/Repositories
  -> Infrastructure/Repositories
```

Если client нужен только HTTP adapter-у одной фичи, держать его внутри этой фичи. В module-level
`Shared` переносить только те clients, которые реально являются публичным межфичевым/межмодульным
контрактом.

1. [x] Вынести service-key проверку из HTTP controllers.
   - Убрать повторяющийся `guard()` из `VehicleCatalogController`, `VehicleCrmController`,
     `NomenclatureCrmController`.
   - Сделать middleware/support adapter для проверки `X-Service-Key` по имени config key.
   - Зарегистрировать middleware alias в `bootstrap/app.php`.
   - На `routes/api.php` явно разделить ключи для catalog API и CRM API.
   - Добавить feature-тесты на `401` без ключа/с неверным ключом и успешный ответ с корректным ключом.

2. [x] Упростить HTTP presenters.
   - Выбрать единый стиль для `page`, `collection`, `detail` response shape.
   - Убрать пустые wrappers, которые только вызывают `toArray()`, либо заменить их общим
     `HttpArrayPresenter`/`PagePresenter`.
   - Не обходить presenter напрямую в controller, как сейчас делает `NomenclatureCrmController::show()`.

3. [x] Разрезать public shared clients, которые сейчас сами читают БД.
   - Public contract оставить в `Shared/Domain/Contracts/Clients`.
   - Реализацию делать тонким `Application/Clients` owner-фичи или module shared application,
     если client агрегирует несколько owner-сценариев.
   - Client вызывает use cases/query services через ports; SQL остается только в
     `Infrastructure/Repositories`.
   - `Vehicles/Shared/Infrastructure/Clients/VehiclesApplicabilityClient` должен перестать строить
     SQL сам.
   - Вынести текущие запросы `frontWiperSpecifications()`, `rearWiperSpecifications()`,
     `resolveModificationIdByMsAndModId()` в явные read ports/use cases/repositories owner-слоя
     Vehicles Catalog или отдельной read-фичи, если Catalog станет слишком широким.
   - `Warehouse/Shared/Infrastructure/Clients/WarehouseApplicabilityClient` должен перестать строить
     SQL сам.
   - Вынести текущие запросы `activeApplicabilityKits()` и `kitExists()` в явные read
     ports/use cases/repositories owner-слоя Warehouse Catalog/Kit.
   - Public client оставить тонкой входной точкой модуля: перевод публичного контракта, ошибки,
     orchestration, но не SQL-query builder.
   - Обновить provider bindings `VehiclesServiceProvider` и `WarehouseServiceProvider` на новые
     реализации clients.

4. [x] Ввести read clients по конкретным внешним потребителям.
   - `VehicleCrmClient` для CRM read сценариев Vehicles.
   - `VehicleCatalogClient` для catalog/store read сценариев Vehicles.
   - `NomenclatureCrmClient` для CRM read сценариев Warehouse nomenclature.
   - Controllers могут вызывать эти clients вместо набора отдельных use cases, если client остается
     тонким фасадом сценариев и не содержит SQL/бизнес-правил.
   - Не смешивать в одном client методы для CRM, catalog/store и Applicability.
   - Добавить domain contracts, application implementations и DI bindings в соответствующих
     `CatalogServiceProvider`.

5. [x] Зафиксировать strict architecture gate.
   - Добавить architecture tests или статический скрипт, который запрещает:
     `Domain -> Application/Infrastructure/Presentation`,
     обычный `Application -> Infrastructure/Presentation`,
     `Application -> чужая Feature\Domain`,
     `Application -> другой Module`, кроме разрешенного `Templates` shared-kernel,
     `Infrastructure/Clients -> DB::/Model::query()` для public clients.
   - `Maintenance` временно занести в allowlist до отдельного разбора.
   - Включать gate после переноса public clients и exception leakage, чтобы тест фиксировал целевое
     состояние, а не уже известные долги.

## 9. DTO, factories и repository API cleanup

1. [x] Разрешить простую механическую сериализацию в DTO.
   - DTO могут иметь `toArray()` и `fromArray()` для собственного состояния, enum values,
     вложенных DTO/list DTO и простой сборки request DTO из массива.
   - В DTO не переносить framework-зависимости: HTTP Request/Response, Rabbit classes, Validator,
     config lookup, Eloquent/paginator mapping, DB, filesystem/external client payload adapters.
   - Если сборка зависит от Eloquent/SQL projection, paginator, нескольких источников данных,
     внешнего API shape или сложной нормализации, оставлять factory/adapter.

2. [x] Сократить DTO factories, которые делают только механическую сборку из одного projection.
   - Проверить и по возможности заменить на `DTO::fromArray()`:
     `VehicleCrmEngineDTOFactory`, `VehicleCrmFeatureOptionDTOFactory`,
     `VehicleCrmFeatureValueOptionDTOFactory`, `VehicleCrmListItemDTOFactory`,
     `VehicleCrmManufacturerOptionDTOFactory`, `VehicleCrmPaginationMetaDTOFactory`,
     `NomenclatureCrmBrandOptionDTOFactory`, `NomenclatureCrmPaginationMetaDTOFactory`.
   - Оставить фабрики, если они собирают DTO из нескольких частей, paginator, collections,
     нормализуют JSON/list fields, строят label или используют feature-local resolver.

3. [x] Не резать фабрики, которые владеют реальной сборкой.
   - Оставить как factories/adapters:
     `VehicleCrmDetailDTOFactory`, `VehicleCrmModificationDTOFactory`,
     `VehicleCrmPartSpecificationDTOFactory`, `VehicleCrmSearchItemDTOFactory`,
     `VehicleCrmPageDTOFactory`, `NomenclatureCrmListItemDTOFactory`,
     `NomenclatureCrmTypeOptionDTOFactory`, `NomenclatureCrmSearchItemDTOFactory`,
     `NomenclatureCrmPageDTOFactory`.

4. [x] Проверить похожие lookup-методы внутри одного repository.
   - Объединять только методы с одним смыслом "найти сущность по альтернативному ключу".
   - Кандидаты:
     `Vehicles/Import/ManufacturerRepository::findByName()/findByMfaId()`,
     `Vehicles/Import/EngineRepository::findByEngId()/findByCodeEngine()`,
     `Warehouse/Import/NomenclatureRepository::findById()/findByPartNumber()`,
     `Vehicles/Catalog/ManufacturerRepository::findById()/findByMfaId()`.
   - Использовать typed lookup DTO там, где это снижает дублирование без потери читаемости.
   - Не объединять методы, которые возвращают другой shape: ids, collections, paginated pages или
     специально отфильтрованные выборки.

5. [x] Уточнить допустимые scalar read methods в repositories.
   - Разрешить узкие scalar reads вроде `exists`, `count`, `nextId`, если это атомарное чтение для
     сценария без бизнес-логики и записи.
   - Не превращать такие методы в отдельный service только ради формы, если repository остается
     чистым read port.
   - Синхронизировать это правило с root `ARCHITECTURE.md`, где сейчас есть более жесткая
     формулировка про scalar read aggregates.

## 10. Тестовый cleanup

1. [x] Сначала закрыть feature-тестами бизнес-сценарии и доменные правила.
   - REST read/write boundaries: успешный ответ, `404`, auth errors, response shape.
   - Rabbit handlers: valid/invalid payload, idempotency по `operation_id`, result events.
   - Import/export/calculation: бизнес-исходы, failures, файлы, rows.

2. [x] После этого убрать перегруженные unit-тесты.
   - Удалять или переписывать unit-тесты, которые проверяют только порядок mock-вызовов
     repositories/commands без бизнес-исхода.
   - Оставить unit-тесты для чистых правил, deterministic algorithms, validation/mapping edge cases
     и узких architecture regression gates.
   - Не удалять тест, пока его важный сценарий не покрыт feature/domain-rule тестом.

## 11. Документация и cleanup

1. [x] Обновить `ARCHITECTURE.md`.
   - Убрать устаревшие ссылки на `plan.md`/`refactor-ms.md`.
   - Уточнить, что для межсервисных Rabbit/REST контрактов используется `operation_id`.
   - Оставить `runId` только там, где это внутренний контекст импорта, если он действительно еще используется.
   - Зафиксировать послабление по DTO `toArray()`/`fromArray()`.
   - Зафиксировать допустимость узких scalar read methods в repositories.
   - Зафиксировать, что `VehicleCrmReadQueryFactory`/`NomenclatureCrmReadQueryFactory` и
     `TypeTemplateResolver` остаются feature-local.
   - Зафиксировать запрет SQL в public clients и отсутствие `Log::info/debug` в production code.

2. [x] Обновить `.ai-factory/ARCHITECTURE.md` теми же правилами, если нужно.

3. [x] Создать или обновить `docs/runbook.md`.
   - Как выполнить Rabbit setup.
   - Как проверить S3.
   - Как запустить workers.
   - Как локально проверить `dan-vehicles` flow вместе с `dan-center`.

4. [x] После переключения сущностей удалить старые deprecated планы/документы и не держать параллельные источники правды.
