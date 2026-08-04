# План интеграции `dan-center` и `dan-vehicles`

> Контекст: `dan-vehicles` уже вынес часть доменов из `dan-center` в чистую архитектуру:
> `Vehicles`, `Warehouse`, `Applicability`, `Templates`. Сервис остается headless. `dan-center`
> сохраняет Filament/UI, MpSale/CRM-оркестрацию и внешние пользовательские сценарии.

## Статус на 2026-07-30

### Уже сделано

- `dan-center` подключен к Rabbit через пакет `pkmstudio/rabbit-transport` для интеграции с
  `dan-vehicles`.
- В обоих сервисах используется единый correlation id: `operation_id`. Отдельный `run_id` для
  межсервисного контракта не используем.
- События результатов в `dan-vehicles` приведены к явным именам:
  - `VEHICLES_IMPORT_COMPLETED`;
  - `VEHICLES_FILE_EXPORTED`;
  - `VEHICLES_CATALOG_MUTATION_COMPLETED`;
  - `WAREHOUSE_IMPORT_COMPLETED`;
  - `WAREHOUSE_FILE_EXPORTED`;
  - `WAREHOUSE_CATALOG_MUTATION_COMPLETED`;
  - `APPLICABILITY_IMPORT_COMPLETED`;
  - `APPLICABILITY_FILE_EXPORTED`;
  - `APPLICABILITY_CALCULATION_COMPLETED`.
- Rabbit bindings очищены и заново созданы setup-командами в обоих сервисах:
  - команды `crm.vehicles.*` попадают в `vehicles.inbox`;
  - result events `vehicles.#`, `warehouse.#`, `applicability.#` попадают в `crm.inbox`;
  - старые parse/review/audit bindings в `dan-center` сохранены.
- В `dan-center` экспорт Vehicles из Filament переведен с локального `Excel::download(...)` на
  асинхронную Rabbit-команду `VEHICLES_EXPORT_FILE_REQUESTED`.
- Начальное Filament-уведомление при клике по экспорту показывает, что запрос отправлен, и выводит
  `operation_id`.
- `dan-center` принимает `VEHICLES_FILE_EXPORTED` и отправляет Filament database notification
  пользователю.
- Для успешного экспорта Vehicles текст завершения приведен к UX-формату "Запрос на экспорт
  завершен" с кнопкой "Открыть файл".
- Таблица `dan_vehicle_operations` признана лишней для текущего сценария, runtime больше в нее не
  пишет, модель и миграция удалены из `dan-center`, локальная таблица сброшена.
- В `dan-center` локально выбран правильный Postgres с 7 пользователями:
  `DB_HOST=host.docker.internal`, database `dan_center`.
- S3-поток для экспорта проверен на общем endpoint `https://s3.dan.center`, bucket
  `uploads-parser`; перед выдачей ссылки `dan-center` проверяет существование файла.
- В локальной БД `dan-vehicles` очищены единичные проблемные данные по щеткам, из-за которых падал
  экспорт.
- Создан пакет wire contracts: `/home/user/projects/packages/dan-wire-contracts`, composer package
  `pkmstudio/dan-wire-contracts`. Внутри `src` только сервисные границы `Vehicles`, `CRM`, `Parse`;
  для `Vehicles` структура приведена к виду сервиса:
  `Vehicles/Modules/{Vehicles|Warehouse|Applicability}/Features/{Feature}/DTO`.
- В `dan-center` переведены на Rabbit:
  - Vehicles import/export;
  - Engines import/export каталога;
  - Warehouse nomenclature import/export по типу;
  - Warehouse kit import/export;
  - Warehouse pack dimension import;
  - Applicability import/export/calculation.
- В `dan-vehicles` добавлен inbound `APPLICABILITY_CALCULATION_REQUESTED` с routing key
  `crm.applicability.calculate`.
- Calculation приведен ближе к схеме Vehicles import/export: `user_id` больше не протаскивается в
  `CalculateKitApplicabilityUseCase` и domain event `KitApplicabilityRecalculated`; связь
  `operation_id -> user_id` хранится как application/integration context для result notification.
- В `dan-vehicles` начат REST read API для CRM: `VehicleCrmController` в feature `Catalog`,
  endpoints `GET /api/v1/vehicles`, `GET /api/v1/vehicles/{id}`, `GET /api/v1/vehicles/search`.
- В `dan-center` добавлен тонкий REST client для Vehicles read API.
- `VehicleCrmController` в `dan-vehicles` разгружен: query/filter/search/sort вынесены в
  Catalog Application use cases и Infrastructure repository.
- В `dan-center` добавлен отдельный REST-backed Filament resource `VehiclesRest` для сравнения со
  старым экраном. Старый `Vehicles` resource оставлен на Eloquent с прежними routes
  `/vehicles`, `/vehicles/create`, `/vehicles/{record}/edit`; REST-прототип доступен отдельно по
  `/vehicles-rest` и использует `Table::records(...)`.
- Для Vehicles в `dan-center` добавлены минимальные Rabbit-backed actions create/edit/delete.
  Это промежуточный CRUD без старой Eloquent form с repeaters; nested-сценарии остаются отдельной
  задачей.
- Vehicles Filament table дополнительно разгружен: общие import/export toolbar actions вынесены в
  `VehiclesToolbarActions`, а Rabbit create/edit/delete actions вынесены в
  `VehiclesRestMutationActions`. REST table теперь остается в основном декларацией колонок,
  фильтров и datasource.
- Vehicles detail REST расширен вложенными данными: `modifications` с привязанными `engines` и
  `part_specifications` с feature/template/details.
- В `dan-center` для Vehicles восстановлена форма в стиле старого Filament resource:
  `Section/Grid/Repeater/Select/TextInput` для основных данных, параметров деталей, модификаций и
  моторов. Эта форма подключена в отдельном `VehiclesRest` resource; данные hydrate-ятся из REST
  detail, а сохранение идет через Rabbit commands.
- Для REST-backed формы Vehicles добавлены option endpoints в `dan-vehicles`:
  features, feature values и detail templates. Select-ы больше не должны читать вынесенные
  справочники из локальных Eloquent-моделей `dan-center`.
- Локальная Docker-связка настроена так, чтобы `dan-center` видел `dan-vehicles` по текущему
  `DAN_VEHICLES_BASE_URL=http://dan-vehicles`: nginx `dan-vehicles` получил alias `dan-vehicles` в
  `dan-shared`, app `dan-center` подключен к `dan-shared`.
- В README пакета и плане зафиксировано правило DTO ownership: wire DTO ответа принадлежит
  публичному контракту сервиса, который публикует API/event; consumer не дублирует wire DTO, а
  маппит его в локальный UI/Application DTO при необходимости.

### Частично сделано

- Пакет `pkmstudio/dan-wire-contracts` подключен в `composer.json` обоих сервисов, lock обновлен.
  Runtime-код еще не переведен на использование классов пакета.
- Из Filament на Rabbit переведены heavy actions, где уже есть готовые команды. Старые локальные
  catalog mutations/create/update/delete и сервисные destructive actions пока не переведены.
- `dan-center` умеет получать result event и слать notification, но постоянный worker/Horizon для
  `crm.inbox` нужно закрепить в docker/deploy, чтобы не запускать `php artisan horizon` вручную.
- REST read-side начат только для CRM Vehicles. Filament table для Vehicles уже читает REST,
  остальные вынесенные домены пока остаются на старых Eloquent-моделях `dan-center`.

### Следующие задачи

1. Закрепить worker/Horizon в `dan-center` для обработки `crm.inbox` и Filament notification jobs.
2. Повторить `rabbit-transport:setup` в stage/prod после новых bindings, особенно для
   `crm.applicability.calculate`. Локально setup уже выполнен.
3. Перевести runtime-код на DTO/enums из `pkmstudio/dan-wire-contracts`.
4. Продолжить рефакторинг `dan-center`: убрать бизнес-сборку payload/операций из всех Filament
   table/page files в отдельные integration/application services. Для Vehicles первый шаг сделан,
   но Filament должен остаться тонким UI-слоем и в остальных вынесенных доменах.
5. Довести REST read API до Warehouse/Applicability и добавить контрактные тесты.
6. На базе готового Vehicles REST-backed table отточить UX/permissions/cache invalidation и затем
   переносить остальные сущности по аналогии.
7. Перевести runtime-код Vehicles vertical slice на DTO/enums из `pkmstudio/dan-wire-contracts`.
8. Инвентаризировать все оставшиеся зависимости `dan-center` от `App\Models\Vehicles` и
   `App\Models\Warehouse` за пределами уже тронутого export action.
9. Доработать контракт `PART_SPECIFICATION_CREATE_REQUESTED`: сейчас create требует
   `part_specification.id`, поэтому Filament вынужден просить ID у пользователя. Целевой вариант —
   server-generated id или отдельный внешний идентификатор.
10. Довести dynamic details формы до полного набора шаблонов по сущностям: для Vehicle сейчас
    восстановлен wiper-шаблон, остальные шаблоны относятся к Engine и должны появиться в
    соответствующем REST-backed ресурсе Engines.

## Что найдено

### `dan-vehicles`

- Архитектура описана в `ARCHITECTURE.md`: module-first + feature-first, Domain/Application не
  импортируют инфраструктуру, синхронные query-вызовы идут через clients, входящие сообщения
  RabbitMQ обрабатываются в `Infrastructure/Messaging/Handlers`, исходящие публикуются через
  `Infrastructure/Notifications`.
- RabbitMQ уже подключен через `pkmstudio/rabbit-transport`.
- `config/rabbit-transport.php` уже содержит входящие команды от CRM:
  - import: `VEHICLES_IMPORT_FILE_REQUESTED`, `ENGINES_IMPORT_FILE_REQUESTED`,
    `WAREHOUSE_NOMENCLATURE_IMPORT_FILE_REQUESTED`, `APPLICABILITY_IMPORT_FILE_REQUESTED`, ...
  - export: `VEHICLES_EXPORT_FILE_REQUESTED`, `WAREHOUSE_KIT_EXPORT_FILE_REQUESTED`,
    `APPLICABILITY_EXPORT_FILE_REQUESTED`, ...
  - catalog mutations: `VEHICLE_CREATE_REQUESTED`, `WAREHOUSE_KIT_UPDATE_REQUESTED`,
    `PART_SPECIFICATION_DELETE_REQUESTED`, ...
- Исходящие события уже есть:
  - `VEHICLES_IMPORT_COMPLETED`;
  - `WAREHOUSE_IMPORT_COMPLETED`;
  - `VEHICLES_FILE_EXPORTED`;
  - `WAREHOUSE_FILE_EXPORTED`;
  - `APPLICABILITY_IMPORT_COMPLETED`;
  - `APPLICABILITY_FILE_EXPORTED`;
  - `APPLICABILITY_CALCULATION_COMPLETED`;
  - `VEHICLES_CATALOG_MUTATION_COMPLETED`;
  - `WAREHOUSE_CATALOG_MUTATION_COMPLETED`.
- REST read-side начат: подключен `routes/api.php`, добавлен CRM controller для Vehicles read API
  в `Catalog/Presentation/Http/Controllers/VehicleCrmController`.

### `dan-center`

- Для новой интеграции с `dan-vehicles` подключен `pkmstudio/rabbit-transport`. Старый локальный
  RabbitMQ-слой остается для legacy-сценариев.
- `crm.inbox` слушает result events `vehicles.#`, `warehouse.#`, `applicability.#`; старые
  parse/review/audit bindings сохранены.
- Filament-ресурсы вынесенных доменов пока напрямую работают с локальными Eloquent-моделями,
  импортами и экспортами:
  - `Vehicles`, `Engines`: модели `App\Models\Vehicles\*`, локальные Excel imports/exports,
    nested repeaters для `partSpecifications`, `modifications`, `engines`;
  - `Nomenclatures`, `Kits`, `Brands`, `PakDimensions`, `Types`, `Vendors`, `Drivers`: модели
    `App\Models\Warehouse\*`, локальные imports/exports, Eloquent relationships;
  - `Kits` также запускает `KitApplicabilityJob`;
  - `Nomenclatures/EditNomenclature` вручную привязывает применяемость через локальные relations.
- MpSale/Feedback/Analytics в `dan-center` продолжают читать старые `Vehicles/Warehouse` модели,
  значит вынос справочников затрагивает не только Filament CRUD.

## Целевая граница сервисов

### RabbitMQ: всё, что меняет состояние или запускает тяжелую операцию

Через RabbitMQ идут:

- create/update/delete справочников;
- import XLSX/CSV;
- export XLSX/CSV;
- пересчет применяемости;
- события результата операций;
- события изменений, на которые `dan-center` должен реагировать, например MpCard invalidation.

`dan-center` публикует команды в `application.events` с routing key вида
`crm.{domain}.{entity}.{action}`. `dan-vehicles` валидирует payload, запускает use case и публикует
result event.

Первый включенный сценарий: `crm.vehicles.export` -> `VEHICLES_EXPORT_FILE_REQUESTED` ->
`VEHICLES_FILE_EXPORTED`.

### REST: только чтение

Через REST идут:

- списки для таблиц Filament;
- карточка записи для view/edit screen;
- справочники для select/filter options;
- поиск для async-select;
- lightweight preview/resolve endpoints;
- скачивание готовых файлов, если общий storage недоступен напрямую из `dan-center`.

REST не должен выполнять create/update/delete/import/export.

### Владение данными

- `dan-vehicles` владеет таблицами и правилами `Vehicles`, `Warehouse`, `Applicability`.
- `dan-center` не пишет напрямую в эти таблицы и постепенно перестает их читать напрямую.
- `dan-center` хранит только UI state, Filament notifications и CRM-specific mappings. Отдельную
  таблицу operation tracking для текущего сценария не заводим.

## Общий пакет контрактов

Пакет нужен, но не в виде "вынести DTO, которые принимают handlers".

Правильная граница:

- **в пакет выносить wire contracts**:
  - enum логических event names;
  - enum/default map routing keys;
  - DTO payload-ов входящих команд и исходящих событий;
  - serializer/deserializer;
  - validation metadata или JSON Schema/OpenAPI schemas;
  - версию контракта.
- **не выносить внутренние Application/Domain DTO `dan-vehicles`**:
  - `CreateVehicleRequestDTO`, `VehicleMutationRequestDTO`, `ImportRunContextDTO` и похожие DTO
    остаются локальными для фичи;
  - handler в `dan-vehicles` должен адаптировать wire DTO из пакета в локальный request DTO.
- **DTO ответа принадлежит публичному контракту сервиса, который публикует API/event**:
  - REST response `VehicleCrmResource` лежит в
    `Vehicles/Modules/Vehicles/Features/Catalog/Read/DTO`, потому что форму ответа определяет
    `dan-vehicles`;
  - `dan-center` не дублирует wire DTO, а при необходимости маппит его в локальный UI/Application
    DTO.

Фактический пакет:

```text
packages/dan-wire-contracts
  src/Vehicles/
    Modules/
      Vehicles/Features/{Catalog,Import,Export}/...
      Warehouse/Features/{Catalog,Import,Export}/...
      Applicability/Features/{Import,Export,Calculation}/...
    Shared/{DTO,Enums,Results}/...
  src/CRM/
  src/Parse/
```

Текущее состояние пакета:

- добавлены enum логических событий и routing keys для всех текущих Rabbit commands/results
  `dan-vehicles`;
- добавлены Rabbit request DTO для catalog mutations:
  `Vehicle`, `Manufacturer`, `Engine`, `Modification`, `PartSpecification`, `Brand`,
  `Nomenclature`, `PackDimension`, `Kit`;
- добавлен общий result DTO `CatalogMutationCompleted` для
  `VEHICLES_CATALOG_MUTATION_COMPLETED` и `WAREHOUSE_CATALOG_MUTATION_COMPLETED`;
- добавлены package-тесты на полноту enum и round-trip основных mutation DTO.

Версионирование:

- следующим шагом можно добавить `contract_version` в DTO/envelope; в текущем wire payload его еще
  нет;
- добавление nullable поля = minor;
- удаление/переименование/изменение смысла поля = новая версия payload/event name;
- `operation_id` обязателен для идемпотентности и корреляции. Отдельный `run_id` в межсервисном
  контракте не используем.

## Формат сообщений

Единый envelope:

```json
{
  "name": "VEHICLE_UPDATE_REQUESTED",
  "data": {
    "contract_version": 1,
    "operation_id": "uuid-or-business-id",
    "user_id": 123,
    "operation": "update",
    "vehicle": {}
  }
}
```

Правила:

- `name` диспетчеризует handler.
- routing key выбирается publisher-ом, например `crm.vehicles.update`.
- `operation_id` уникален для бизнес-команды, чтобы повтор Rabbit-сообщения не создавал дубль.
- `operation_id` используется для import/export/calculation и результата операции.
- payload всегда snake_case.
- ошибки бизнес-валидации не должны бесконечно ретраиться: handler логирует/публикует rejected
  result и ack/delete сообщения.
- техническая ошибка дает retry, после лимита сообщение уходит в DLQ.

## Клиенты в `dan-center`

### Rabbit clients

Создать слой `App\Services\DanVehicles` или `App\Integrations\DanVehicles`:

```text
app/Integrations/DanVehicles/
  Rabbit/DanVehiclesPublisher.php
  Rabbit/VehiclesCommandClient.php
  Rabbit/WarehouseCommandClient.php
  Rabbit/ApplicabilityCommandClient.php
  Rest/DanVehiclesHttpClient.php
  Rest/VehiclesReadClient.php
  Rest/WarehouseReadClient.php
  Rest/ApplicabilityReadClient.php
  DTOs/...
```

Командные клиенты:

- `VehiclesCommandClient`
  - `requestImport(...)`;
  - `requestExport(...)`;
  - `createVehicle(...)`, `updateVehicle(...)`, `deleteVehicle(...)`;
  - аналогично для manufacturers, engines, modifications, part specifications.
- `WarehouseCommandClient`
  - `requestImport(...)`, `requestExport(...)`;
  - CRUD для brands, nomenclatures, pack dimensions, kits.
- `ApplicabilityCommandClient`
  - `requestImport(...)`;
  - `requestExport(...)`;
  - `requestCalculation(...)`;
  - later: manual attach/sync commands, если UI сохранит ручную привязку.

Read clients:

- `VehiclesReadClient`
  - `paginateVehicles(filters, sort, page)`;
  - `getVehicle(id|ms_id)`;
  - `searchVehicles(query)`;
  - `getVehicleEditSnapshot(id)`;
  - `paginateEngines`, `getEngine`, `searchEngines`;
  - `listManufacturers`, `listFeatures`, `listFeatureValues`, `listTemplates`.
- `WarehouseReadClient`
  - `paginateNomenclatures`, `getNomenclature`, `searchNomenclatures`;
  - `paginateKits`, `getKit`, `searchKits`;
  - `listBrands`, `listTypes`, `listPackDimensions`, `listVendors`, `listDrivers`.
- `ApplicabilityReadClient`
  - `kitIdsForTarget(targetType, targetId)`;
  - `paginateVehicleApplicabilityExportPreview`;
  - `paginateEngineApplicabilityExportPreview`;
  - `getApplicabilityForNomenclature(partNumber/type)`, если нужен текущий UI "Получить
    применяемость TEC DOC".

`dan-center` также нужен consumer исходящих событий `dan-vehicles`:

- обновлять operation status;
- слать Filament notification пользователю;
- показывать ссылку на generated export/import failure report;
- запускать CRM-specific reactions, например invalidation MpCard.

## Клиенты/API в `dan-vehicles`

### REST controllers

Добавить `routes/api.php` и подключить его в `bootstrap/app.php`.

Минимальный read API:

```text
GET /api/v1/vehicles
GET /api/v1/vehicles/{id}
GET /api/v1/vehicles/{id}/edit-snapshot
GET /api/v1/vehicles/search
GET /api/v1/engines
GET /api/v1/engines/{id}
GET /api/v1/engines/search
GET /api/v1/manufacturers
GET /api/v1/features
GET /api/v1/feature-values
GET /api/v1/detail-templates

GET /api/v1/warehouse/brands
GET /api/v1/warehouse/types
GET /api/v1/warehouse/nomenclatures
GET /api/v1/warehouse/nomenclatures/{id}
GET /api/v1/warehouse/kits
GET /api/v1/warehouse/kits/{id}
GET /api/v1/warehouse/pack-dimensions
GET /api/v1/warehouse/vendors
GET /api/v1/warehouse/drivers

GET /api/v1/applicability/targets/{targetType}/{targetId}/kit-ids
GET /api/v1/applicability/kits/{kitId}/targets
```

Контроллеры остаются в `Presentation/Http/Controllers`, вызывают Application query use cases или
Repository ports. Возвращать `Resource`/DTO, не Eloquent models.

### REST query capabilities для Filament

Для таблиц Filament недостаточно простого `index`. Нужны:

- фильтры по тем же полям, что есть сейчас в таблицах;
- сортировка;
- полнотекстовый/частичный поиск;
- include/counts для отношений, например `manufacturer`, `nomenclatures_count`, `type`,
  `pak_dimension`;
- endpoints для option lists с `q`, `limit`.

Лучше сделать explicit query DTO:

```text
page, per_page, sort, filter[...], search, include[]
```

## Модернизация Filament

### Этап 1. Сохранить UX, заменить heavy actions

Сначала не переписывать таблицы полностью. Заменить import/export/calculation actions на Rabbit:

- `UploadXlsxAction` загружает файл в общий disk/path, затем публикует `*_IMPORT_FILE_REQUESTED`.
- Export actions публикуют `*_EXPORT_FILE_REQUESTED`, сразу показывают "экспорт запущен".
- Calculation actions публикуют `APPLICABILITY_CALCULATION_REQUESTED` или новый event, если его
  нужно добавить.
- Result events возвращают статус и файл, `dan-center` показывает Filament notification.

Это быстро убирает прямой запуск старых imports/exports из `dan-center`.

### Этап 2. REST-backed Filament resources

Для `Vehicles`, `Engines`, `Nomenclatures`, `Kits` сделать REST-backed подход без локальной read
replica в `dan-center`:

- таблицы читают данные через REST;
- view/edit страницы получают snapshot через REST;
- save/delete не пишут Eloquent, а публикуют Rabbit-команду;
- после публикации показывается pending status; фактический результат приходит event-ом.

Filament из коробки ожидает Eloquent query, но projection/read tables в `dan-center` не используем:
это вторая копия данных, пусть и не source of truth. Поэтому целевой вариант один:

- custom Filament pages/livewire tables работают с REST paginator;
- filters/sort/search маппятся в query DTO клиента `dan-vehicles`;
- forms получают REST snapshot и на submit публикуют Rabbit-команду;
- старые Eloquent resources для вынесенных доменов остаются только временным legacy до замены.

### Этап 3. Формы и nested relations

Текущие forms используют Eloquent relationships:

- `VehicleForm`: `parent`, `manufacturer`, `partSpecifications`, `modifications`, `engines`;
- `EngineForm`: `partSpecifications`;
- `KitForm`: `nomenclatures`, `pakDimension`;
- `NomenclatureForm`: `brand`, `type`, details templates.

Их нужно перевести на command DTO:

- форма собирает flat/nested state;
- page action маппит state в один или несколько Rabbit commands;
- сложные nested изменения лучше разбить:
  - `update vehicle`;
  - `create/update/delete part specification`;
  - `update modification`;
  - `sync modification engines`, если такой сценарий действительно нужен.

Не стоит пытаться эмулировать Eloquent `Repeater::relationship()` поверх REST. Это будет хрупко.

### Этап 4. Старые модели

После переключения UI:

- запретить create/update/delete старых `App\Models\Vehicles/*` и `App\Models\Warehouse/*` в
  `dan-center`;
- удалить или переименовать старые imports/exports, чтобы их случайно не использовали;
- пройти MpSale/Feedback/Analytics и заменить прямые зависимости на REST read clients или
  отдельные CRM-owned связи/кэши, если данные действительно принадлежат CRM.

## MpSale и invalidation

В `dan-center` есть зависимость MpSale от Vehicles/Warehouse/Applicability. Это не должно ехать в
`dan-vehicles`.

Нужный поток:

1. `dan-vehicles` публикует granular domain/integration events:
   - `VEHICLE_CHANGED`;
   - `ENGINE_CHANGED`;
   - `MODIFICATION_CHANGED`;
   - `PART_SPECIFICATION_CHANGED`;
   - `WAREHOUSE_NOMENCLATURE_CHANGED`;
   - `WAREHOUSE_KIT_CHANGED`;
   - `APPLICABILITY_CHANGED`.
2. `dan-center` слушает эти события и запускает свои jobs:
   - invalidate cards by vehicle/engine/spec;
   - invalidate by nomenclature/kit;
   - rebuild generated marketplace params.

Сейчас в `plans/plan-new.md` уже отмечено, что это отдельная кросс-доменная задача. Ее нужно
делать после базовой связки import/export/catalog mutation result.

## Пошаговый план работ

### Фаза 0. Инвентаризация контрактов

Статус: частично сделано.

1. [ ] Зафиксировать полный список Filament actions и старых imports/exports, которые должны быть
   заменены Rabbit-командами. Частично закрыто для export Vehicles.
2. [ ] Зафиксировать все прямые зависимости `dan-center` от `App\Models\Vehicles` и
   `App\Models\Warehouse` за пределами Filament: MpSale, imports, exports, widgets, policies.
3. [ ] Составить таблицу "старый local class -> новый Rabbit command или REST endpoint".

### Фаза 1. Общий package и transport alignment

Статус: частично сделано.

1. [x] Создать пакет `pkmstudio/dan-wire-contracts`.
2. [x] Вынести туда базовые wire DTO/enums для текущей связки Vehicles/CRM.
   Дополнено Rabbit DTO/enums для catalog mutations Vehicles/Warehouse и result DTO мутаций.
3. [x] Подключить пакет в `composer.json` обоих сервисов и обновить lock-файлы.
4. [ ] В `dan-center` заменить локальный `RabbitMessageDTO/OutboundEventsEnum` для новых интеграций на
   пакетные contracts.
   Начато: `config/rabbit-transport.php`, result handlers/notification labels и REST-backed
   Vehicles/Nomenclatures actions используют enum/DTO из `pkmstudio/dan-wire-contracts`.
5. [x] Подключить `dan-center` к `pkmstudio/rabbit-transport` для новой связки с `dan-vehicles`.
6. [ ] Включать HMAC только после того, как оба сервиса будут готовы к одинаковому envelope.

### Фаза 2. Rabbit topology

Статус: сделано локально, нужно повторять в целевых окружениях.

1. [x] В `dan-center` добавить bindings на исходящие события `dan-vehicles`:
   - `vehicles.*`;
   - `warehouse.*`;
   - `applicability.*`.
2. [x] В `dan-vehicles` выполнить `rabbit-transport:setup` локально.
3. [x] В `dan-center` выполнить `rabbit-transport:setup` локально.
4. [x] Проверить routing:
   - `crm.vehicles.import` попадает в `vehicles.inbox`;
   - `vehicles.import.completed` попадает в `crm.inbox`;
   - DLQ/poison behavior работает одинаково.
5. [x] Повторить setup локально после новых bindings (`crm.applicability.calculate`).
6. [ ] Повторить setup в stage/prod после деплоя новых bindings.

### Фаза 3. Уведомления результата в `dan-center`

Статус: сделано для result notification без отдельной таблицы.

1. [x] Отказаться от таблицы `dan_vehicle_operations` для текущего варианта.
2. [x] Удалить runtime-запись операций в БД из `dan-center`.
3. [x] На старте операции показывать Filament notification с `operation_id`.
4. [x] На result event отправлять Filament database notification пользователю.
5. [x] Для export success показывать кнопку "Открыть файл".
6. [x] Перед выдачей ссылки проверять, что файл реально существует в S3.
7. [ ] Закрепить постоянный worker/Horizon для `crm.inbox`, чтобы уведомления не зависели от
   ручного запуска `php artisan horizon`.

### Фаза 4. Import/export/calculation из Filament

Статус: начато.

1. [x] Перевести Vehicles export actions на Rabbit.
2. [x] Перевести Vehicles import actions на Rabbit.
3. [x] Перевести Engines import/export actions на Rabbit, кроме export применяемости двигателей:
   под него пока нет `export_type` в `dan-vehicles`.
4. [x] Перевести Warehouse nomenclature/kit/pack-dimension import/export actions на Rabbit, где эти
   actions есть в Filament.
5. [x] Перевести Applicability import/export/calculation actions на Rabbit.
6. [ ] Удалить прямые вызовы `Excel::download(new ...)`, локальные `UploadXlsxAction->importerClass(...)`
   для вынесенных доменов.

### Фаза 5. Read REST в `dan-vehicles`

Статус: начато.

1. [x] Добавить `routes/api.php` и мягкий service-to-service auth через `DAN_VEHICLES_READ_API_KEY`.
2. [x] Реализовать CRM read endpoints для Vehicles в `Catalog/Presentation/Http/Controllers/VehicleCrmController`.
3. [x] Отрефакторить `VehicleCrmController`: вынести query/filter/search/sort из controller в
   Catalog Application read use cases и DTO/resource mappers.
4. [ ] Реализовать read endpoints для Warehouse.
5. [ ] Реализовать read endpoints для Applicability.
6. [ ] Добавить OpenAPI или контрактные тесты на response shape.

### Фаза 6. Filament read migration

Статус: начато на Vehicles.

1. [ ] Отрефакторить текущие `dan-center` Filament actions: вынести публикацию Rabbit payload,
   upload в S3 и notification orchestration из table/page classes в отдельные сервисы.
   Для Vehicles часть сделана: toolbar import/export actions и REST create/edit/delete actions
   вынесены из table classes в отдельные action classes.
2. [ ] Заменить `Resource::$model` паттерн для вынесенных доменов на custom Filament pages.
3. [x] Реализовать REST paginator/filter adapter для Vehicles:
   - `page`;
   - `per_page`;
   - `sort`;
   - `search`;
   - `filter[...]`.
4. [ ] Переписать list screens `Vehicles`, `Engines`, `Nomenclatures`, `Kits`.
   Для Vehicles сделан отдельный REST resource `VehiclesRest` рядом со старым Eloquent resource,
   чтобы сравнить функционал перед заменой.
5. [ ] Переписать view/edit screens на REST snapshot.
   Для Vehicles view реализован как REST-backed modal snapshot; detail REST уже показывает
   modifications/engines и part specifications. Edit-форма Vehicles восстановлена через
   Section/Grid/Repeater на REST state; nested save для part specifications идет Rabbit-командами.
6. [ ] Для select/search полей использовать async REST option endpoints.
7. [x] Не создавать projection/read replica tables в `dan-center`.

### Фаза 7. Filament write migration

Статус: начато на Vehicles.

1. [ ] Переписать create/edit/delete страниц Vehicles/Engines на Rabbit commands.
   Для Vehicles добавлены минимальные table actions create/edit/delete через Rabbit; полноценные
   страницы и nested-сценарии еще не перенесены.
2. [ ] Переписать create/edit/delete Warehouse entities на Rabbit commands.
3. [ ] Nested repeaters заменить на explicit actions/sections:
   - `PartSpecification` CRUD;
   - `Kit` composition update;
   - applicability manual attach/sync.
   Для Vehicles начато: просмотр part specifications и добавление part specification через Rabbit
   перенесены внутрь edit-формы как Repeater. Модификации с моторами отображаются Repeater-ом
   read-only; их write-сценарии нужно довести отдельно.
4. [ ] Result events отправляют Filament notifications и при необходимости инвалидируют локальный
   HTTP cache.

### Фаза 8. Consumers для CRM-specific reactions

Статус: не начато.

1. [ ] Добавить в `dan-vehicles` granular change events там, где их пока нет.
2. [ ] В `dan-center` подписаться на changes.
3. [ ] Перенести старые `InvalidateMpCardsBy*Job` на новые incoming events.
4. [ ] Проверить, что `dan-vehicles` ничего не знает про MpSale.

### Фаза 9. Очистка

Статус: не начато.

1. [ ] Удалить или deprecated-пометить старые imports/exports вынесенных доменов в `dan-center`.
2. [ ] Убрать прямые writes в старые модели.
3. [ ] Проверить `rg "App\\\\Models\\\\Vehicles|App\\\\Models\\\\Warehouse"` и закрыть оставшиеся
   зависимости по списку исключений.
4. [ ] Обновить README/operations runbook.

## Первые практические задачи

1. [x] Подключить Rabbit-пакет в `dan-center` для новой интеграции.
2. [x] Добавить в `dan-center` publisher для `dan-vehicles` Rabbit events.
3. [x] Перевести export Vehicles в Filament на Rabbit.
4. [x] Принимать `VEHICLES_FILE_EXPORTED` в `dan-center` и показывать Filament notification.
5. [x] Убрать runtime-зависимость от `dan_vehicle_operations`.
6. [ ] Закрепить постоянный worker/Horizon в `dan-center`.
7. [x] Перевести import Vehicles на Rabbit.
8. [x] Перевести Warehouse/Applicability heavy actions на Rabbit, где есть готовые команды.
9. [ ] Поднять read REST для `Warehouse` list/detail.
10. [x] Сделать прототип одной REST-backed Filament table без Eloquent, например `Vehicles`.
11. [x] Создать отдельный package wire-contracts.
12. [ ] Перевести runtime-код обоих сервисов на DTO/enums из `pkmstudio/dan-wire-contracts`.
    Начато в `dan-center`: Rabbit config, inbound result handler, VehiclesRest/NomenclaturesRest
    mutation actions и upload/export actions используют package contracts на boundary.
    Начато в `dan-vehicles`: catalog mutation handlers нормализуют валидированный inbound payload
    через package request DTO; catalog mutation notification services публикуют package result DTO.
13. [ ] Отрефакторить `dan-center`, чтобы Filament files не содержали бизнес-логику интеграции.
    Начато на Vehicles: toolbar actions и REST mutation actions вынесены из table classes.
14. [x] Отрефакторить `dan-vehicles`, чтобы REST controllers не содержали query/business logic.
15. [x] Убрать `userId` из `KitApplicabilityRecalculated` и use case расчета применяемости.

## Вопросы

1. **Решено:** общий storage disk есть. Используем S3-compatible endpoint `s3.dan.center`, bucket
   `uploads-parser`; оба сервиса должны читать/писать одни и те же import/export/report paths.
2. **Решено:** UX операций в Filament асинхронный: "запрос принят", результат приходит
   уведомлением/result event.
3. **Решено:** projection/read replica tables в `dan-center` не используем.
4. Предварительно: старые таблицы `Vehicles/Warehouse` в `dan-center` должны уйти для всех доменов,
   которые переехали в `dan-vehicles`. Нужно отдельно инвентаризировать MpSale/analytics consumers
   и заменить их на REST read clients или CRM-owned данные.
5. **Решено:** для межсервисной корреляции везде используем `operation_id`; отдельный `run_id` не
   нужен.
6. **Решено:** статусы `completed_with_errors` и `completed_with_failures` не надо разводить как
   разные состояния без отдельного смысла. Для UI это один сценарий "завершено с ошибками".
7. **Решено:** completion notification по export Vehicles не пишет operation table, а использует
   Filament database notifications.
8. **Решено:** отдельный package wire-contracts создан:
   `/home/user/projects/packages/dan-wire-contracts`.
9. **Открыто:** какие старые таблицы `Vehicles/Warehouse` в `dan-center` остаются временно для
   MpSale/analytics, а какие можно отключать сразу после переключения Filament?
10. **Открыто:** нужна ли обратная совместимость с текущим `dan-center` Rabbit envelope
   (`name` + `data`) без `contract_version`, или можно сразу вводить версионированный envelope?
11. **Открыто:** как `dan-center` будет авторизоваться в REST API `dan-vehicles`: достаточно одного
   внутреннего API-ключа в `.env`, или сервисы будут доступны только в закрытой сети без отдельного
   ключа?
12. **Открыто:** в `dan-center` сейчас есть кнопка в редактировании номенклатуры "Получить применяемость TEC DOC":
   она ищет машины/двигатели/модификации по артикулу и вручную привязывает выбранное. Нужно ли
   сохранить именно эту ручную кнопку, или ее заменяем импортом/расчетом применяемости?
13. **Открыто:** если таблица `dan_vehicle_operations` была создана в stage/prod, ее нужно будет
   удалить вручную или отдельной cleanup-миграцией. В локальной БД она уже удалена.
