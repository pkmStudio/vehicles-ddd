# Описание проекта dan-vehicles

## Обзор

`dan-vehicles` — Laravel-сервис каталога автомобилей, складских номенклатур и применяемости комплектов. Сервис импортирует и экспортирует CSV/Excel-данные, обрабатывает внешние RabbitMQ-события, хранит каталог в PostgreSQL и публикует результаты интеграционных сценариев во внешнюю инфраструктуру.

Проект уже развивается как модульный монолит: бизнес-код расположен в `app/Modules/*`, а доменные модули разделены на фичи с явными слоями `Domain`, `Application`, `Infrastructure` и `Presentation`.

## Основные возможности

- Импорт справочников Vehicles из CSV/Excel: производители, модели, модификации, двигатели, группы двигателей и спецификации.
- Экспорт Vehicles, Warehouse и Applicability в Excel, включая справочные листы и шаблонные `details`.
- Расчет и импорт применяемости комплектов для автомобилей.
- Каталог Warehouse: бренды, типы, номенклатуры, комплекты, упаковка, свойства комплектов, аудит адаптеров дворников.
- Интеграция с RabbitMQ через пакет `pkmstudio/rabbit-transport`.
- Интеграция с MoySklad через локальный пакет `pkmstudio/moysklad-client`.
- Фоновые задачи через Laravel Queue и Horizon.
- Хранение экспортов и файловых артефактов через Laravel Filesystem/S3.

## Технологический стек

- **Язык:** PHP 8.4
- **Фреймворк:** Laravel 13
- **База данных:** PostgreSQL 17 в Docker Compose; локально также присутствует SQLite-файл для стандартного Laravel bootstrap
- **ORM:** Eloquent ORM, изолированный внутри `Infrastructure`
- **Очереди:** Laravel Queue, Horizon, RabbitMQ transport
- **Импорт/экспорт:** `maatwebsite/excel`
- **DTO/Data:** `spatie/laravel-data`
- **Файлы/S3:** `league/flysystem-aws-s3-v3`
- **Frontend tooling:** Vite 8, Tailwind CSS 4, `laravel-vite-plugin`
- **Тесты:** PHPUnit 12 через `php artisan test`
- **Форматирование:** Laravel Pint
- **Контейнеризация:** Docker Compose с сервисами `app`, `horizon`, `nginx`, `pgsql` и внешней сетью `dan-shared`

## Модули и границы

- `app/Modules/Vehicles` — каталог автомобилей, импорт, экспорт, внешние catalog mutation-сценарии и maintenance-команды.
- `app/Modules/Warehouse` — складской каталог, импорт/экспорт, packaging, kit properties, MoySklad и аудит адаптеров дворников.
- `app/Modules/Applicability` — импорт, экспорт и расчет применяемости.
- `app/Modules/Templates` — shared-kernel для типизированных шаблонов `details`, enum-словарей, сборки из строк и рендера в Excel.

Верхнеуровневого `app/Modules/Shared` нет: технический workflow и infrastructure helpers живут внутри
конкретной фичи, даже если это приводит к небольшому дублированию. Внутри доменных модулей целевая
раскладка — `Features/<Feature>/{Domain,Application,Infrastructure,Presentation}` плюс `Shared` для
публичных событий, enum-словарей и module-level инфраструктуры.

## Архитектура

Подробные правила архитектуры находятся в `.ai-factory/ARCHITECTURE.md`.

**Паттерн:** модульный монолит с feature-first раскладкой и Clean Architecture / DDD-правилами зависимостей внутри фич.

## Нефункциональные требования

- **Логирование:** использовать Laravel logging (`Log::...`) для входящих сообщений, валидации payload и интеграционных ошибок; бизнес-невалидные сообщения брокера логируются и отбрасываются без retry.
- **Обработка ошибок:** доменные ошибки выражать специализированными exception-классами в `Domain/Exceptions`; внешние workflow должны возвращать явные result DTO или notification DTO.
- **Идемпотентность:** для внешних импортов учитывать `runId` и cache-based guards, чтобы повторные broker-сообщения не запускали один сценарий дважды.
- **Границы модулей:** не импортировать чужие Application-сервисы напрямую; межфичевые синхронные вызовы оформлять через локальные client ports и infrastructure adapters.
- **Безопасность:** секреты и credentials хранить в `.env`; MCP Postgres использует `${DATABASE_URL}`, RabbitMQ/S3/MoySklad параметры приходят из Laravel config/env.
- **Тестируемость:** покрывать импорт/экспорт, mapping, factories, use cases и message handlers через `tests/Feature/*` и `tests/Unit/*`.
