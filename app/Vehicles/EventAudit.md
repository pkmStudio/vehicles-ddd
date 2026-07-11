# Vehicle module — аудит событий

## 1) Уже существующие доменные события

| Класс | Строка | Что это | Зачем |
|---|---:|---|---|
| `app/Vehicles/Domain/Events/AbstractImportCompleted.php:9` | 9 | `AbstractImportCompleted` (базовый абстрактный payload) | Базовый класс результата импорта с `userId` и `cacheKey`. |
| `app/Vehicles/Domain/Events/Manufacturer/ManufacturerCommandImported.php:10` | 10 | `ManufacturerCommandImported` | Факт завершения импорта производителей. |
| `app/Vehicles/Domain/Events/Vehicle/VehicleCommandImported.php:10` | 10 | `VehicleCommandImported` | Факт завершения импорта ТС. |
| `app/Vehicles/Domain/Events/Engine/EngineCommandImported.php:10` | 10 | `EngineCommandImported` | Факт завершения импорта двигателей. |
| `app/Vehicles/Domain/Events/Modification/ModificationCommandImported.php:10` | 10 | `ModificationCommandImported` | Факт завершения импорта модификаций. |
| `app/Vehicles/Domain/Events/Vehicle/VehicleImportCompleted.php:9` | 9 | `VehicleImportCompleted` (наследует `AbstractImportCompleted`) | Завершение много-листового импорта ТС + ключ отчёта. |
| `app/Vehicles/Domain/Events/Engine/EngineImportCompleted.php:9` | 9 | `EngineImportCompleted` (наследует `AbstractImportCompleted`) | Завершение много-листового импорта двигателей + ключ отчёта. |
| `app/Vehicles/Domain/Events/Engine/EngineCrossImportCompleted.php:9` | 9 | `EngineCrossImportCompleted` (наследует `AbstractImportCompleted`) | Завершение кросс-импорта кодов двигателей в группы + ключ отчёта. |
| `app/Vehicles/Domain/Events/EnginesAndModificationsReady.php:14` | 14 | `EnginesAndModificationsReady` | Служебный факт: оба импорта (engine + modification) завершены. |

## 2) Где сейчас диспатчатся события

| Класс | Строка | Событие | Что делает |
|---|---:|---|---|
| `app/Vehicles/Infrastructure/Imports/Manufacturer/ManufacturerCommandImport.php:81` | 81 | `ManufacturerCommandImported` | Запускается после импорта производителей (`AfterImport`). |
| `app/Vehicles/Infrastructure/Imports/Vehicle/VehicleCommandImport.php:86` | 86 | `VehicleCommandImported` | Запускается после импорта ТС (`AfterImport`). |
| `app/Vehicles/Infrastructure/Imports/Engine/EngineCommandImport.php:76` | 76 | `EngineCommandImported` | Запускается после импорта двигателей (`AfterImport`). |
| `app/Vehicles/Infrastructure/Imports/Modification/ModificationCommandImport.php:86` | 86 | `ModificationCommandImported` | Запускается после импорта модификаций (`AfterImport`). |
| `app/Vehicles/Infrastructure/Imports/Vehicle/VehicleMultiSheetImport.php:76` | 76 | `event(new VehicleImportCompleted($userId, $cacheKey))` | После импорта всех листов ТС. |
| `app/Vehicles/Infrastructure/Imports/Engine/EngineMultiSheetImport.php:76` | 76 | `event(new EngineImportCompleted($userId, $cacheKey))` | После импорта всех листов двигателей. |
| `app/Vehicles/Infrastructure/Imports/Engine/EngineSparkPlugSpecificationImport.php:117` | 117 | `event(new EngineImportCompleted($userId, $cacheKey))` | После импорта свечей по модификациям (тоже итоговый импорт). |
| `app/Vehicles/Infrastructure/Imports/Engine/EngineCrossImport.php:125` | 125 | `event(new EngineCrossImportCompleted($userId, $cacheKey))` | После импорта кросс-строк группировки двигателей. |

## 3) Где сейчас обрабатываются в listeners (EventServiceProvider)

| Класс | Строка | На каком событии | Что делает |
|---|---:|---|---|
| `app/Vehicles/Infrastructure/Providers/EventServiceProvider.php:32` | 32 | `ManufacturerCommandImported` | Инициализирует `StartVehicleCommandImportListener` и `StartEngineCommandImportListener`. |
| `app/Vehicles/Infrastructure/Providers/EventServiceProvider.php:35` | 35 | `VehicleCommandImported` | Инициализирует `StartModificationCommandImportListener`. |
| `app/Vehicles/Infrastructure/Providers/EventServiceProvider.php:41` | 41 | `EngineCommandImported`, `ModificationCommandImported` (через `subscribe`) | `EngineModificationReadinessSubscriber` собирает обе фазы и выставляет готовность. |
| `app/Vehicles/Infrastructure/Providers/EventServiceProvider.php:46` | 46 | `EnginesAndModificationsReady` | Инициализирует `StartEngineModificationCommandImportListener`. |
| `app/Vehicles/Infrastructure/Providers/EventServiceProvider.php:51` | 51 | `VehicleImportCompleted` | Инициализирует `ReportImportResultListener`. |
| `app/Vehicles/Infrastructure/Providers/EventServiceProvider.php:52` | 52 | `EngineImportCompleted` | Инициализирует `ReportImportResultListener`. |
| `app/Vehicles/Infrastructure/Providers/EventServiceProvider.php:53` | 53 | `EngineCrossImportCompleted` | Инициализирует `ReportImportResultListener`. |

## 4) Рекомендуемый следующий слой событий (не реализовано)

> Ниже — предложения по местам, где событийность полезна для реакций/интеграций.

| Класс | Предложение по строке | Событие | Зачем |
|---|---:|---|---|
| `app/Vehicles/Infrastructure/Exports/Vehicle/VehicleMultiSheetExport.php:26` | метод `download()` | `VehicleExportCompleted`, `VehicleExportFailed` | Единый сигнал об успехе/ошибке экспорта ТС для нотификаций и аудита. |
| `app/Vehicles/Infrastructure/Exports/Engine/EngineMultiSheetExport.php:26` | метод `download()` | `EngineExportCompleted`, `EngineExportFailed` | То же для экспорта двигателей. |
| `app/Vehicles/Application/Export/Services/VehicleExportService.php:38` | метод `buildExportPlan()` / `getMainRows()` | `VehicleExportRequested` | Отдельный факт запроса на экспорт (ограничения, логирование, очереди). |
| `app/Vehicles/Application/Export/Services/EngineExportService.php:35` | метод `buildExportPlan()` / `getMainRows()` | `EngineExportRequested` | Отдельный факт запроса на экспорт двигателей. |
| `app/Vehicles/Application/Import/Services/Engine/AssignEngineGroupService.php:23` | метод `execute()` после `setGroupId()` | `EngineGroupAssigned`, `EngineGroupReassigned` | Для downstream-реакций на изменение связки кода двигателя с группой. |
| `app/Vehicles/Application/Import/Services/EngineModification/LinkEngineModificationFromRowService.php:27` | метод `execute()` после `syncWithoutDetaching()` | `EngineModificationLinkCreated` | Реакции на установку связи `engine <-> modification` (кэш, индексация, отчёты). |
| `app/Vehicles/Application/Import/Services/Vehicle/VehicleWiperSpecificationImportService.php:34` | метод `execute()` после батчевого апсерта | `VehicleWiperSpecsUpdated` | Событие факта массового обновления дворников. |
| `app/Vehicles/Application/Import/Services/Engine/UpsertSparkPlugSpecByModificationService.php:33` | метод `execute()` после цикла по двигателям | `EngineSparkPlugSpecsUpdated` | Факт пакетного обновления свечей по модификации (включая skipped-результат). |
| `app/Vehicles/Application/Import/Services/Engine/UpsertEngineSparkPlugSpecService.php:31` | метод `execute()` после upsert | `EngineSparkPlugSpecUpdated` | Мелкий факт обновления свечей конкретного двигателя. |
| `app/Vehicles/Application/Import/Services/Reporting/ReportImportResultService.php:25` | метод `execute()` после `reporter->store()` / catch | `ImportReportGenerated`, `ImportReportFailed` | Убрать `TODO`-развязку и формализовать итог импорта. |
| `app/Vehicles/Infrastructure/Messaging/Enums/OutboundEventsEnum.php:14-17` | enum | `IMPORT_SUCCEEDED`, `IMPORT_FAILED`, `VEHICLE_UPSERTED`, `ENGINE_UPSERTED` | Интеграционные события для внешних сервисов (например, Filament). |
