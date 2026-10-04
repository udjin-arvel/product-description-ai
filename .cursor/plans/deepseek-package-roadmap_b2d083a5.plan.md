---
name: deepseek-package-roadmap
overview: Поэтапно создать с нуля Composer-пакет `arvelov/product-description-ai` (`ProductDescriptionAI\`) с независимым ядром, поддержкой Base64/URL-изображений и адаптерами Laravel, Symfony и Yii 2. Каждая стадия ниже рассчитана на одну отдельную итерацию и заканчивается проверяемым результатом.
todos:
  - id: stage-1-foundation
    content: "Этап 1: проверить DeepSeek API-контракт и создать каркас Composer-пакета"
    status: completed
  - id: stage-2-dtos
    content: "Этап 2: реализовать DTO, источники изображений и валидацию"
    status: completed
  - id: stage-3-payload
    content: "Этап 3: реализовать prompt/payload builders"
    status: completed
  - id: stage-4-client
    content: "Этап 4: реализовать PSR-18 клиент и parser ответа"
    status: completed
  - id: stage-5-resilience
    content: "Этап 5: добавить исключения, retry и backoff"
    status: completed
  - id: stage-6-service
    content: "Этап 6: собрать сервис, кэширование и наблюдаемость"
    status: completed
  - id: stage-7-laravel
    content: "Этап 7: реализовать и протестировать Laravel-адаптер"
    status: completed
  - id: stage-8-symfony
    content: "Этап 8: реализовать и протестировать Symfony-адаптер"
    status: completed
  - id: stage-9-yii
    content: "Этап 9: реализовать и протестировать Yii 2-адаптер"
    status: completed
  - id: stage-10-ci
    content: "Этап 10: настроить интеграционные тесты и CI-матрицу"
    status: completed
  - id: stage-11-release
    content: "Этап 11: завершить документацию и подготовить релиз 1.0"
    status: completed
isProject: false
---

# План реализации PHP-расширения Product Description AI

Основа: [`.project/product-description-ai-tech-spec.md`](.project/product-description-ai-tech-spec.md). Перед кодированием нужно проверить актуальный контракт DeepSeek Vision: название модели и мультимодальные возможности из ТЗ нельзя безопасно считать неизменными; endpoint и model будут конфигурируемыми.

## Этап 1 — Каркас пакета и фиксация API-контракта
- Проверить по актуальной официальной документации DeepSeek endpoint, модель, формат `image_url`, `response_format` и лимиты.
- Создать `composer.json` для `arvelov/product-description-ai`, PSR-4 `ProductDescriptionAI\ => src/`, PHP 8.1+, PSR-18 и PSR-17 зависимости.
- Подключить PHPUnit, PHPStan и PHP-CS-Fixer; создать базовую структуру `src/Core`, `src/Laravel`, `src/Symfony`, `src/Yii`, `tests`.
- Зафиксировать подтверждённые решения и матрицу совместимости в README.
- Критерий готовности: зависимости устанавливаются, autoload работает, пустые test/static-analysis/style проверки проходят.

## Этап 2 — Контракты, DTO и валидация входа
- Реализовать `GenerateRequest`, `GenerateResponse`, `Usage` и типизированные источники изображения `Base64Image`/`UrlImage`.
- Проверять title, MIME (`jpeg/png/webp` согласно подтверждённому API), Base64, URL, размер и допустимые значения `detail`, temperature и max tokens.
- Ввести отдельные исключения конфигурации и валидации; не допускать framework-зависимостей в ядре.
- Критерий готовности: unit-тесты покрывают валидные значения, границы и все основные отказы.

## Этап 3 — Формирование промпта и payload
- Вынести системный промпт в `PromptBuilder`, поддержать переопределение языка, объёма, стиля и дополнительных инструкций.
- Реализовать `PayloadBuilder` для Base64 и внешнего URL, параметра `detail`, структурированного JSON-ответа и конфигурируемой модели.
- Исключить прямую интерполяцию пользовательских данных в системные инструкции; title передавать как пользовательский контент.
- Критерий готовности: snapshot/array-тесты подтверждают точный payload для обоих типов изображений.

## Этап 4 — PSR-18 клиент и разбор ответа
- Реализовать `DeepSeekClient` через `Psr\Http\Client\ClientInterface` и PSR-17 request/stream factories; не использовать Guzzle-специфичные `post()`/`json` options.
- Добавить Bearer auth, configurable endpoint/model/timeout и безопасное декодирование ответа.
- Реализовать parser содержимого `choices[0].message.content`, JSON description/keywords и `usage`.
- Критерий готовности: HTTP-клиент полностью проверен mock PSR-18 ответами без реальных сетевых запросов.

## Этап 5 — Ошибки, retry и устойчивость
- Создать иерархию исключений API: authentication, rate limit, invalid request, server, transport и malformed response.
- Повторять только transport errors, 408/429 и 5xx; учитывать `Retry-After`, exponential backoff и jitter, не ретраить 4xx без основания.
- Инъецировать clock/sleeper для быстрых детерминированных тестов; не включать API key и изображение в сообщения ошибок.
- Критерий готовности: тесты подтверждают количество попыток, backoff, классификацию HTTP-кодов и отсутствие утечки секретов.

## Этап 6 — Сервис генерации, кэш и наблюдаемость
- Собрать `ProductDescriptionService`: validate → payload → client → parse → response DTO.
- Добавить опциональные framework-agnostic контракты кэша и логирования (PSR-16/PSR-3), стабильный cache key без хранения полного изображения и TTL по умолчанию 24 часа.
- Логировать request ID, модель, latency и token usage, но не API key/Base64/полный prompt.
- Критерий готовности: service tests покрывают успешную генерацию, cache hit/miss, отключённый кэш и метрики usage.

## Этап 7 — Адаптер Laravel
- Реализовать `DeepSeekServiceProvider`, config publish/merge, container bindings и facade.
- Настроить адаптацию Laravel HTTP/cache/logger к контрактам ядра; добавить package auto-discovery.
- Проверить Laravel 10/11/12 в совместимой CI-матрице и документировать DI/facade usage.
- Критерий готовности: package test app разрешает сервис из контейнера, читает env/config и выполняет mock-вызов обоими способами.

## Этап 8 — Адаптер Symfony
- Реализовать `DeepSeekBundle`, configuration tree и DI extension для Symfony 6.1/7.
- Связать Symfony PSR-18 client, cache и logger с ядром, поддержать env-конфигурацию без хранения ключа в контейнерном дампе/логах.
- Добавить KernelTestCase для загрузки bundle и валидации конфигурации.
- Критерий готовности: тестовое kernel-приложение компилирует контейнер и получает публичный сервис генерации.

## Этап 9 — Адаптер Yii 2
- Реализовать `DeepSeekComponent` с конфигурируемыми свойствами, lazy initialization и явным DI HTTP-клиента.
- Подключить Yii cache/logger через небольшие bridge-классы; не создавать скрыто конкретный Guzzle client внутри компонента.
- Добавить unit/integration tests конфигурации application component.
- Критерий готовности: Yii-приложение получает компонент и выполняет mock-генерацию с cache/log bridges.

## Этап 10 — Интеграционные тесты и CI-матрица
- Настроить GitHub Actions для PHP 8.1–8.4, framework-совместимых dependency sets, PHPUnit, PHPStan и style check.
- Добавить opt-in smoke test реального DeepSeek API через secret, не запускаемый на fork PR; использовать небольшой fixture.
- Проверить упаковку Composer (`validate --strict`, install lowest/highest dependencies) и отсутствие конфликтов адаптеров.
- Критерий готовности: обязательная CI-матрица зелёная; сетевой smoke test документирован и изолирован.

## Этап 11 — Документация и релиз 1.0
- Описать установку, общий core API, Base64/URL, конфигурацию каждого фреймворка, кэш, retry, ошибки и примеры результата.
- Добавить security guidance, troubleshooting, CHANGELOG, LICENSE и release checklist.
- Провести финальную проверку публичного API и создать semver-тег только после успешного smoke test на подтверждённой модели.
- Критерий готовности: новый пользователь может установить пакет и выполнить первый запрос, следуя только README.

## Границы версии 1.0
- Включены Base64 и внешний URL; Files API отложен.
- Ядро не зависит от Laravel/Symfony/Yii и не использует их глобальные функции.
- Модель и endpoint не захардкожены как неизменяемые значения.
- Реальный API key никогда не хранится в репозитории и не требуется для unit/integration test suite.