<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| RabbitTransport — конфигурация транспортного слоя AMQP
|--------------------------------------------------------------------------
|
| Пакет НЕ ссылается на классы приложения (App\...). Все привязки
| «событие → обработчик», исходящие события и топология exchange/queue
| объявляются приложением здесь (публикуется как config/rabbit-transport.php).
|
| Секции:
|  - connection  — имя queue-connection из config/queue.php;
|  - inbound     — реестр входящих событий event-name → [Class, Method];
|  - outbound    — исходящие события name → дефолтный routing key;
|  - setup       — топология exchange/queue/bindings для setup-команды.
|
| --------------------------------------------------------------------------
| WIRE-КОНТРАКТ (два независимых токена — сохраняются при развязке от enum):
| --------------------------------------------------------------------------
| (а) per-message ROUTING KEY — например `crm.audit.{table}.{event}`.
|     Генерируется отправителем и передаётся в RabbitMQPublisher::publish()
|     вторым аргументом. Если аргумент пуст — publisher берёт дефолт из
|     секции `outbound` по логическому имени события.
| (б) поле тела `name` — логическое имя события, например `AUDIT_RECORDED`.
|     Кладётся в тело сообщения через RabbitMessageDTO->name. Консьюмер
|     диспетчеризует обработку по этому полю через реестр `inbound`.
|
| ВАЖНО: имя события (`name`, токен б) ≠ routing key (токен а). В старом
| App\...\OutboundEventsEnum это были enum->name (AUDIT_RECORDED) и
| enum->value (crm.audit.recorded) соответственно. Здесь они разнесены явно:
| `name` живёт в DTO, дефолтный routing key — в секции `outbound`.
|
*/

use App\Vehicles\Catalog\Infrastructure\Messaging\Handlers\EngineMutationRequestedHandler;
use App\Vehicles\Catalog\Infrastructure\Messaging\Handlers\ManufacturerMutationRequestedHandler;
use App\Vehicles\Catalog\Infrastructure\Messaging\Handlers\ModificationMutationRequestedHandler;
use App\Vehicles\Catalog\Infrastructure\Messaging\Handlers\VehicleMutationRequestedHandler;
use App\Vehicles\Export\Infrastructure\Messaging\Handlers\ExportFileRequestedHandler;
use App\Vehicles\Import\Infrastructure\Messaging\Handlers\ImportFileRequestedHandler;
use App\Warehouse\Export\Infrastructure\Messaging\Handlers\ExportFileRequestedHandler as WarehouseExportFileRequestedHandler;

return [

    /*
    | Имя queue-connection (config/queue.php), через которое работает
    | publisher и inbox-consumer. Историческое имя в dan-center — rabbitmq_inbox.
    */
    'connection' => env('RABBIT_TRANSPORT_CONNECTION', 'rabbitmq_inbox'),

    /*
    | Сколько секунд ждать подтверждения publisher confirms перед таймаутом.
    */
    'publish_confirm_timeout' => (float) env('RABBIT_TRANSPORT_CONFIRM_TIMEOUT', 5.0),

    /*
    | Опциональная HMAC-подпись сообщений (аутентификация источника +
    | целостность тела). Симметричный общий секрет: publisher подписывает,
    | консьюмер проверяет тем же ключом. Секрет НЕ передаётся по проводу —
    | только подпись. Выключено по умолчанию.
    |
    | При enabled=true publisher оборачивает тело в конверт
    | {alg, sig, payload}, где payload — дословный JSON исходного {name, data},
    | а sig = hash_hmac(alg, payload, secret). Консьюмер проверяет подпись по
    | дословным байтам payload (без переупаковки) и при несовпадении/отсутствии
    | подписи логирует и удаляет сообщение (fail-closed).
    |
    | ВАЖНО: publisher и консьюмер должны иметь одинаковые enabled/algo/secret.
    | HMAC не идентифицирует конкретный сервис (общий ключ) и сам по себе не
    | защищает от replay — при необходимости добавьте в data timestamp/nonce.
    */
    'security' => [
        'hmac' => [
            'enabled' => (bool) env('RABBIT_TRANSPORT_HMAC_ENABLED', false),
            'algo' => env('RABBIT_TRANSPORT_HMAC_ALGO', 'sha256'),
            'secret' => env('RABBIT_TRANSPORT_HMAC_SECRET', ''),
        ],
    ],

    /*
    | Настройки консьюмера. release_delay — задержка (сек) перед повторной
    | доставкой при ошибке обработки.
    |
    | max_attempts — максимальное число попыток обработки. Безопасный дефолт — 3,
    | чтобы битое («poison») сообщение не зацикливалось вечно. Значение 0 — это
    | явный opt-in на историческое поведение с бесконечным release; при нём
    | консьюмер помечает в логе retry_policy=infinite на каждый повтор.
    |
    | poison_action — финальное действие после исчерпания попыток. Безопасный
    | дефолт — dead_letter (сообщение помечается failed и reject-ится; для реальной
    | пересылки в DLQ очередь должна быть объявлена с x-dead-letter-exchange —
    | включите секцию setup.dead_letter). Значение delete безвозвратно удаляет сообщение.
    */
    'consumer' => [
        'release_delay' => (int) env('RABBIT_TRANSPORT_RELEASE_DELAY', 20),
        'max_attempts' => (int) env('RABBIT_TRANSPORT_MAX_ATTEMPTS', 3),
        'poison_action' => env('RABBIT_TRANSPORT_POISON_ACTION', 'dead_letter'),

        /*
        | Защита от вредоносного/битого входящего payload. max_body_bytes —
        | предельный размер тела сообщения (0 — без лимита). max_json_depth —
        | максимальная глубина JSON при декодировании. Тело сверх лимитов или с
        | битым/слишком глубоким JSON логируется и удаляется (без retry).
        */
        'max_body_bytes' => (int) env('RABBIT_TRANSPORT_MAX_BODY_BYTES', 262144),
        'max_json_depth' => (int) env('RABBIT_TRANSPORT_MAX_JSON_DEPTH', 64),
    ],

    /*
    | Реестр входящих событий (T1.3): имя события из тела сообщения (поле `name`)
    | → обработчик [class-string, method]. Консьюмер диспетчеризует по этому ключу.
    |
    | Пример (объявляется приложением):
    |   'AUDIT_RECORDED' => [\App\Services\Audit\AuditInboxService::class, 'upsert'],
    */
    'inbound' => [
        'VEHICLES_IMPORT_FILE_REQUESTED' => [
            ImportFileRequestedHandler::class,
            'handle',
        ],
        'ENGINES_IMPORT_FILE_REQUESTED' => [
            ImportFileRequestedHandler::class,
            'handle',
        ],
        'MODIFICATIONS_IMPORT_FILE_REQUESTED' => [
            ImportFileRequestedHandler::class,
            'handle',
        ],
        'ENGINE_GROUPS_IMPORT_FILE_REQUESTED' => [
            ImportFileRequestedHandler::class,
            'handle',
        ],
        'SPARK_PLUGS_IMPORT_FILE_REQUESTED' => [
            ImportFileRequestedHandler::class,
            'handle',
        ],

        /*
        | Входящие запросы на экспорт каталога. Один Handler на оба типа —
        | конкретный Excel-адаптер выбирается по data.export_type
        | (см. Export\Infrastructure\Messaging\Handlers\ExportFileRequestedHandler
        | и Export\Application\Factories\ExportFileFactory).
        */
        'VEHICLES_EXPORT_FILE_REQUESTED' => [
            ExportFileRequestedHandler::class,
            'handle',
        ],
        'ENGINES_EXPORT_FILE_REQUESTED' => [
            ExportFileRequestedHandler::class,
            'handle',
        ],
        'WAREHOUSE_NOMENCLATURE_EXPORT_FILE_REQUESTED' => [
            WarehouseExportFileRequestedHandler::class,
            'handle',
        ],
        'WAREHOUSE_KIT_EXPORT_FILE_REQUESTED' => [
            WarehouseExportFileRequestedHandler::class,
            'handle',
        ],

        'VEHICLE_CREATE_REQUESTED' => [
            VehicleMutationRequestedHandler::class,
            'handle',
        ],
        'VEHICLE_UPDATE_REQUESTED' => [
            VehicleMutationRequestedHandler::class,
            'handle',
        ],
        'VEHICLE_DELETE_REQUESTED' => [
            VehicleMutationRequestedHandler::class,
            'handle',
        ],
        'MANUFACTURER_CREATE_REQUESTED' => [
            ManufacturerMutationRequestedHandler::class,
            'handle',
        ],
        'MANUFACTURER_UPDATE_REQUESTED' => [
            ManufacturerMutationRequestedHandler::class,
            'handle',
        ],
        'MANUFACTURER_DELETE_REQUESTED' => [
            ManufacturerMutationRequestedHandler::class,
            'handle',
        ],
        'ENGINE_CREATE_REQUESTED' => [
            EngineMutationRequestedHandler::class,
            'handle',
        ],
        'ENGINE_UPDATE_REQUESTED' => [
            EngineMutationRequestedHandler::class,
            'handle',
        ],
        'ENGINE_DELETE_REQUESTED' => [
            EngineMutationRequestedHandler::class,
            'handle',
        ],
        'MODIFICATION_CREATE_REQUESTED' => [
            ModificationMutationRequestedHandler::class,
            'handle',
        ],
        'MODIFICATION_UPDATE_REQUESTED' => [
            ModificationMutationRequestedHandler::class,
            'handle',
        ],
        'MODIFICATION_DELETE_REQUESTED' => [
            ModificationMutationRequestedHandler::class,
            'handle',
        ],
    ],

    /*
    | Исходящие события (T1.3): логическое имя → routing key по умолчанию.
    | Per-message routing key может переопределяться отправителем.
    |
    | IMPORT_COMPLETED — статус импорта: completed / completed_with_errors / failed.
    | Содержит run_id, user_id, errors_count и, если есть, путь к отчёту.
    | FILE_EXPORTED — файл сформирован и сохранён в общем хранилище (disk из
    | vehicles.export.output.disk), путь передан в payload; сервис с Filament
    | слушает это событие, чтобы уведомить получателя о готовности каталога.
    | Публикуется из Export\Infrastructure\Notifications\RabbitMqExportNotificationService.
    */
    'outbound' => [
        'FILE_EXPORTED' => 'vehicles.file.exported',
        'WAREHOUSE_FILE_EXPORTED' => 'warehouse.file.exported',
        'IMPORT_COMPLETED' => 'vehicles.import.completed',
        'CATALOG_MUTATION_COMPLETED' => 'vehicles.catalog.mutation.completed',
    ],

    /*
    | Топология для setup-команды (T1.4): exchange, очередь и routing-маски.
    | Маски (например 'crm.audit.#') объявляются на стороне приложения.
    |
    | dead_letter — опциональная DLX/DLQ-топология. Когда enabled=true,
    | setup-команда объявляет dead-letter exchange и dead-letter queue и вешает на
    | основную очередь аргумент x-dead-letter-exchange. Это обязательное условие,
    | чтобы poison_action=dead_letter реально складывал сообщения в DLQ.
    |
    | По умолчанию выключено: аргументы существующей очереди изменить нельзя, и
    | включение DLX требует пересоздания очереди — это осознанное действие.
    | Дефолты имён: DLX = "{queue}.dlx", DLQ = "{queue}.dlq".
    */
    'setup' => [
        'exchange' => env('RABBIT_TRANSPORT_EXCHANGE', 'application.events'),
        'exchange_type' => env('RABBIT_TRANSPORT_EXCHANGE_TYPE', 'topic'),
        'queue' => env('RABBIT_TRANSPORT_QUEUE', 'vehicles.inbox'),

        /*
        | Входящие запросы на импорт/экспорт из CRM.
        | Формат routing key: {source-service}.{entity}.{action}.
        | Каждый routing key соответствует уникальному name в inbound выше,
        | но группа routing key на одно действие (import/export) обрабатывается
        | одним handler-ом. Конкретный адаптер выбирается по data.import_type
        | (импорт) или data.export_type (экспорт).
        */
        'bindings' => [
            'crm.vehicles.import',
            'crm.engines.import',
            'crm.modifications.import',
            'crm.engine-groups.import',
            'crm.spark-plugs.import',
            'crm.vehicles.export',
            'crm.engines.export',
            'crm.warehouse.nomenclatures.export',
            'crm.warehouse.kits.export',
            'crm.vehicles.create',
            'crm.vehicles.update',
            'crm.vehicles.delete',
            'crm.manufacturers.create',
            'crm.manufacturers.update',
            'crm.manufacturers.delete',
            'crm.engines.create',
            'crm.engines.update',
            'crm.engines.delete',
            'crm.modifications.create',
            'crm.modifications.update',
            'crm.modifications.delete',
        ],

        'dead_letter' => [
            'enabled' => (bool) env('RABBIT_TRANSPORT_DLX_ENABLED', false),
            'exchange' => env('RABBIT_TRANSPORT_DLX_EXCHANGE'),
            'exchange_type' => env('RABBIT_TRANSPORT_DLX_EXCHANGE_TYPE', 'fanout'),
            'queue' => env('RABBIT_TRANSPORT_DLQ'),
            'routing_key' => env('RABBIT_TRANSPORT_DLX_ROUTING_KEY', ''),
        ],
    ],

];
