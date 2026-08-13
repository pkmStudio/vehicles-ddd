# AGENTS.md

> Карта проекта для AI-агентов. Обновляйте этот файл при значимых изменениях структуры, модулей или точек входа.

## Обзор проекта

`dan-vehicles` — Laravel 13 сервис для каталога автомобилей, складских номенклатур, импортов/экспортов и применяемости комплектов. Подробное описание находится в `.ai-factory/DESCRIPTION.md`.

## Технологический стек

- **Язык:** PHP 8.4
- **Фреймворк:** Laravel 13
- **База данных:** PostgreSQL 17
- **ORM:** Eloquent ORM внутри `Infrastructure`
- **Очереди:** Laravel Queue, Horizon, RabbitMQ transport
- **Frontend tooling:** Vite 8, Tailwind CSS 4
- **Тесты:** PHPUnit 12, Laravel Pint

## Структура проекта

```text
app/
  Modules/
    Vehicles/        # Каталог автомобилей, импорт, экспорт, catalog mutations, maintenance
    Warehouse/       # Складской каталог, импорт/экспорт, packaging, MoySklad, audits
    Applicability/   # Импорт, экспорт и расчет применяемости
    Templates/       # Shared-kernel для details-шаблонов и Excel mapping
  Providers/         # Laravel service providers верхнего уровня
  Support/           # Локальные support-классы вне бизнес-модулей
bootstrap/           # Laravel bootstrap
config/              # Laravel и модульные config-файлы
database/            # Seeders и локальный SQLite bootstrap-файл
docker/              # Dockerfile, nginx/php/postgres support scripts
public/              # Laravel public entrypoint
resources/           # CSS, JS и Blade resources
routes/              # Laravel routes и console routes
tests/               # Feature/Unit тесты, fixtures и test concerns
plans/               # Исторические и рабочие планы рефакторинга
.ai-factory/         # Контекст AI Factory: описание, архитектура, правила
```

## Ключевые точки входа

| Файл | Назначение |
|---|---|
| `artisan` | CLI entrypoint Laravel и artisan-команд. |
| `bootstrap/app.php` | Bootstrap приложения и route/middleware setup. |
| `bootstrap/providers.php` | Регистрация Laravel service providers. |
| `routes/console.php` | Console route definitions. |
| `routes/api.php` | API routes, сейчас сгруппированы под `v1`. |
| `config/rabbit-transport.php` | Конфигурация RabbitMQ transport. |
| `config/horizon.php` | Конфигурация Horizon. |
| `docker-compose.yml` | Локальные сервисы `app`, `horizon`, `nginx`, `pgsql`. |
| `phpunit.xml` | PHPUnit/Laravel test configuration. |
| `vite.config.js` | Vite/Tailwind asset pipeline. |
| `composer.json` | PHP зависимости, autoload и composer scripts. |
| `package.json` | Node/Vite scripts и frontend dependencies. |

## Документация

| Документ | Путь | Описание |
|---|---|---|
| README | `README.md` | Стандартный Laravel README; не отражает текущие бизнес-модули проекта. |
| Архитектура проекта | `ARCHITECTURE.md` | Детальные архитектурные правила и история целевой module-first / feature-first структуры. |
| AI Factory описание | `.ai-factory/DESCRIPTION.md` | Сводка проекта, стека, модулей и интеграций для AI-агентов. |
| AI Factory архитектура | `.ai-factory/ARCHITECTURE.md` | Краткие рабочие правила архитектуры для AI Factory workflow. |
| Базовые правила | `.ai-factory/rules/base.md` | Автоматически определенные conventions проекта. |
| Менеджерские инструкции | `managment/` | Инструкции по работе в Filament, импортам и экспортам. |

## AI Context Files

| Файл | Назначение |
|---|---|
| `AGENTS.md` | Быстрая карта проекта для AI-агентов и новых участников. |
| `.ai-factory/config.yaml` | Настройки AI Factory: язык, пути, workflow и git defaults. |
| `.ai-factory/DESCRIPTION.md` | Описание проекта и технологического стека. |
| `.ai-factory/ARCHITECTURE.md` | Архитектурные guidelines для AI Factory skills. |
| `.ai-factory/rules/base.md` | Базовые правила и conventions проекта. |
| `ARCHITECTURE.md` | Основной подробный справочник архитектуры проекта. |

## Правила для агентов

- Разделяйте shell-команды вместо склеивания через `&&`, `;` или pipe, когда результат важен для reasoning.
- Неверный пример: `git checkout master && git pull`
- Верный порядок: сначала `git checkout master`, затем `git pull origin master`.
- Не меняйте application code во время `$aif`: этот setup создает только контекст, правила, MCP/skill рекомендации и агентские документы.
- Перед изменением бизнес-кода читайте `ARCHITECTURE.md` и `.ai-factory/ARCHITECTURE.md`.
