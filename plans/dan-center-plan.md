# План `dan-center`

Дата актуализации: 2026-08-11.

## Принятые решения

- `dan-center` владеет пользовательским UI и Filament.
- `dan-center` не держит projection/read-replica таблицы вынесенных доменов `Vehicles`, `Warehouse`, `Applicability`.
- Read-сценарии вынесенных доменов идут в `dan-vehicles` по REST.
- Write/heavy-сценарии идут в `dan-vehicles` через RabbitMQ: import, export, calculation, create, update, delete.
- Межсервисная корреляция идет через `operation_id`.
- Таблица `dan_vehicle_operations` не нужна; состояние операций показывается через Filament notifications.
- `pkmstudio/dan-wire-contracts` является справочником wire DTO/enums на REST/Rabbit границе.
- Внутренние UI/application сценарии не должны протаскивать package DTO глубоко в доменную логику.

## 1. Transport и wire contracts

1. Довести runtime-переход на `pkmstudio/dan-wire-contracts`.
   - Publishers должны собирать Rabbit payload через package DTO/enums.
   - Result handlers должны читать result events через package DTO/enums.
   - REST clients/action-классы должны использовать package DTO/enums только на границе.
   - Внутри UI/application services маппить wire DTO в локальные request/result DTO.

2. Проверить полноту потребляемых контрактов.
   - Vehicles: import/export, catalog mutations, CRM read DTO.
   - Warehouse: import/export, catalog mutations, CRM read DTO для nomenclatures и следующих сущностей.
   - Applicability: import/export/calculation request/result DTO.
   - Shared result DTO: import completed, file exported, catalog mutation completed.

3. Добавить consumer-side контрактные тесты.
   - Тестировать реальные result payload из `dan-vehicles` против consumers `dan-center`.
   - Тестировать REST response samples `dan-vehicles` против REST clients `dan-center`.
   - Тестировать `toArray()/fromArray()` package DTO, которые реально используются в `dan-center`.

4. Закрыть REST-auth на стороне клиента.
   - Выбрать и внедрить один вариант: внутренний API key или строго закрытая docker/network зона.
   - Если выбираем API key, все REST clients должны передавать согласованный service key.

5. HMAC для Rabbit включать только после полной готовности обоих сервисов к одинаковому envelope.

6. Повторить `rabbit-transport:setup` в stage/prod после деплоя новых bindings.
   - Проверить `crm.* -> vehicles.inbox`.
   - Проверить `vehicles.#`, `warehouse.#`, `applicability.# -> crm.inbox`.
   - Отдельно проверить DLQ/poison behavior.

## 2. Filament и интеграционный слой

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

## 3. REST/Rabbit parity

1. Проверить parity UI со старыми Eloquent resources.
   - Vehicles list/search/detail/options.
   - Nomenclature list/search/detail/options.
   - Engines, Manufacturers, Modifications, PartSpecifications.
   - Warehouse Brands, Kits, PackDimensions.
   - Applicability read/import/export/calculation.

2. Проверить write/heavy flow из UI.
   - Import/export requests.
   - Catalog mutations create/update/delete.
   - Applicability calculation requests.
   - Повторные клики/дубликаты не должны создавать второй бизнес-запуск при одном `operation_id`.

3. Проверить обработку result events.
   - `VEHICLES_IMPORT_COMPLETED`.
   - `VEHICLES_FILE_EXPORTED`.
   - `VEHICLES_CATALOG_MUTATION_COMPLETED`.
   - `WAREHOUSE_IMPORT_COMPLETED`.
   - `WAREHOUSE_FILE_EXPORTED`.
   - `WAREHOUSE_CATALOG_MUTATION_COMPLETED`.
   - `APPLICABILITY_IMPORT_COMPLETED`.
   - `APPLICABILITY_FILE_EXPORTED`.
   - `APPLICABILITY_CALCULATION_COMPLETED`.

4. Выровнять статусы UI.
   - Для UI `completed_with_errors` и `completed_with_failures` не разводить без отдельного смысла.
   - Использовать общий статус из wire-contracts.

## 4. Документация и runbook

1. Обновить runbook `dan-center`.
   - Как настроить REST endpoint/key для `dan-vehicles`.
   - Как выполнить Rabbit setup.
   - Как проверить S3 upload/download flow.
   - Как запустить workers/Horizon.
   - Как локально проверить Filament flow через `dan-center`.

2. После переключения сущностей удалить старые deprecated планы/документы и не держать параллельные источники правды.

