# Демо product-description-ai

Локальный стенд: React-интерфейс из [web/](web/) и Laravel API с пакетом `arvelov/product-description-ai` из корня репозитория (path-репозиторий Composer).

## Что поднимается

| Сервис | Порт | Назначение |
|--------|------|------------|
| `web` | 3000 | UI «Студия описаний» (Vite + Bun) |
| `api` | 8080 | Laravel, маршрут `POST /api/generate` |

Браузер отправляет форму на API; Laravel вызывает DeepSeek Vision через фасад `DeepSeek`.

## Запуск

Нужны Docker и Docker Compose.

```bash
cd demo
copy .env.example .env
```

Укажите в `.env` ключ DeepSeek:

```env
DEEPSEEK_API_KEY=sk-...
```

Сборка и старт (первый раз дольше из‑за `composer install` и `bun install`):

```bash
docker compose up --build
```

Откройте **http://localhost:3000**, загрузите JPG/PNG/WebP (до 10 МБ) и введите название товара.

## Переменные окружения

Файл [`.env.example`](.env.example) передаётся контейнеру `api` через `env_file`. Важные параметры:

- `DEEPSEEK_API_KEY` — обязателен для реальной генерации.
- `DEEPSEEK_CACHE_ENABLED=false` — кэш отключён, чтобы «Создать заново» каждый раз ходил в API.
- `DEEPSEEK_TIMEOUT=120` — увеличенный таймаут для Vision-запросов.

UI использует `VITE_API_URL=http://localhost:8080` (задаётся в `docker-compose.yml`).

## Структура

- `web/` — UI «Студия описаний»; страница `/` шлёт `FormData` на `/api/generate`.
- `api/` — минимальное Laravel-приложение: [GenerateController](api/app/Http/Controllers/GenerateController.php), маршрут в [routes/api.php](api/routes/api.php).
- `docker-compose.yml`, `api/Dockerfile`, `web/Dockerfile` — образы для API и фронта.

## Без Docker (опционально)

1. В `demo/api`: `composer install`, скопировать переменные из `demo/.env` в `demo/api/.env`, `php artisan serve --port=8080`.
2. В `demo/web`: `bun install`, `VITE_API_URL=http://localhost:8080 bun run dev`.

Ключ `DEEPSEEK_API_KEY` нужен в обоих случаях.
