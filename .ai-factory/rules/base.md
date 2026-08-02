# Базовые правила проекта

> Автоматически определенные конвенции из анализа кодовой базы. Редактируйте при изменении целевой архитектуры.

## Именование

- Файлы PHP: `PascalCase.php`, имя файла совпадает с именем класса, интерфейсы заканчиваются на `Interface`.
- Классы: `PascalCase`; большинство production-классов объявлены как `final` или `final readonly`, когда наследование не требуется.
- Переменные и методы: `camelCase`.
- Тестовые методы: `test_snake_case`, классы тестов заканчиваются на `Test`.
- Enum-классы: `PascalCaseEnum`; значения enum используются как typed contract, а не как свободные строки.
- DTO/Data-классы: суффиксы `DTO` и `Data`; request/result objects объявляются `final readonly`.

## Структура модулей

- Бизнес-код находится в `app/Modules`.
- Доменные модули `Vehicles`, `Warehouse` и `Applicability` используют feature-first структуру: `Features/<Feature>/{Domain,Application,Infrastructure,Presentation}`.
- `Templates` является shared-kernel модулем для шаблонов `details` и не дробится на `Features`, пока внутри него нет независимых фич.
- `Shared` внутри доменного модуля предназначен для публичных событий, enum-словарей и общей module-level инфраструктуры, а не для удобного складирования сервисов.
- Eloquent-модели, repositories, commands, Excel adapters, message handlers и framework-интеграции остаются в `Infrastructure`.
- Domain содержит contracts, DTO/Data, enums, events и domain exceptions без прямых зависимостей на Laravel facades, Excel, RabbitMQ или Eloquent.

## Обработка ошибок

- Для доменных ошибок использовать специализированные exception-классы в `Domain/Exceptions`.
- Входящие broker payload валидировать до запуска use case; бизнес-невалидные payload логировать и пропускать без выброса retryable exception.
- Application/use case слой возвращает явные result DTO или `null`, когда сценарий осознанно ничего не меняет.
- В местах интеграции допустим `try/catch (Throwable $e)` с переводом ошибки в mutation result, log entry или notification result.

## Логирование

- Использовать Laravel logging (`Log::error`, `Log::warning`, `Log::info`) в infrastructure adapters, message handlers и интеграционных workflow.
- Не протаскивать фасады логирования в Domain.
- Лог-сообщения для входящих интеграций должны содержать контекст: тип события/import, `runId`, `userId`, file path или operation id, когда они доступны.

## Тесты

- Использовать PHPUnit через `php artisan test`.
- Feature-тесты живут в `tests/Feature/<Module>/<Feature>`.
- Unit-тесты живут в `tests/Unit/<Module>/<Feature>`.
- Общие test helpers и traits размещать в `tests/Concerns`.
- CSV/Excel фикстуры хранить в `tests/Fixtures`.
