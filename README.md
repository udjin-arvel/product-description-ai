# arvelov/product-description-ai

Независимая от конкретного фреймворка библиотека PHP для генерации описаний товаров интернет-магазина: на входе - название и картинка, на выходе - продающий текст. Работает через DeepSeek Vision API в формате, совместимом с OpenAI Chat Completions.

## Требования

- PHP 8.1+
- расширение `ext-json`
- HTTP-клиент по PSR-18 (Guzzle, Symfony HttpClient, Laravel HTTP и т. п.)

## Установка

```bash
composer require arvelov/product-description-ai
```

Укажите API-ключ:

```bash
export DEEPSEEK_API_KEY=sk-...
```

## Демо

Интерактивная форма (фото + название товара) на базе Laravel и UI-прототипа в Docker. Подробности — в [demo/README.md](demo/README.md).

```bash
cd demo
copy .env.example .env
docker compose up --build
```

Откройте [http://localhost:3000](http://localhost:3000). В `.env` укажите `DEEPSEEK_API_KEY`.

## Быстрый старт

```php
use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Factory\ServiceFactory;
use ProductDescriptionAI\Core\Image\Base64Image;
use ProductDescriptionAI\Core\Image\UrlImage;

$config = new ClientConfig(apiKey: getenv('DEEPSEEK_API_KEY') ?: '');
$factory = new Psr17Factory();

$service = ServiceFactory::createService(
    $config,
    new Client(['timeout' => 60]),
    $factory,
    $factory,
);

$request = new GenerateRequest(
    title: 'Wireless headphones',
    image: new Base64Image(base64_encode(file_get_contents('product.jpg')), 'jpeg'),
    // или: image: new UrlImage('https://cdn.example.com/product.jpg'),
);

$response = $service->generate($request);

echo $response->description;
print_r($response->keywords);
```



## Laravel

Пакет подключается через auto-discovery (`DeepSeekServiceProvider`). Конфиг можно опубликовать с помощью команды:

```bash
php artisan vendor:publish --tag=deepseek-config
```

В `.env`:

```env
DEEPSEEK_API_KEY=sk-...
DEEPSEEK_MODEL=deepseek-flash
DEEPSEEK_CACHE_ENABLED=true
```

Базовый промпт задаётся в опубликованном `config/deepseek.php` ключами `system_prompt` и `user_prompt`. `null` оставляет встроенный текст. В своём шаблоне можно использовать `{language}`, `{style}`, `{title}`, `{min_words}`, `{max_words}`, `{length}` и `{extra_instructions}`.

Длина описания необязательна: `minWords` и `maxWords` в `GenerateRequest`. Если оба не заданы, промпт не требует конкретного объёма.

Вызов через фасад или DI:

```php
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Image\UrlImage;
use ProductDescriptionAI\Laravel\Facades\DeepSeek;

$response = DeepSeek::generate(new GenerateRequest('Mug', new UrlImage('https://...')));
```

Если Guzzle по умолчанию не подходит, зарегистрируйте в контейнере свой `Psr\Http\Client\ClientInterface`.

## Symfony

Подключите бандл в `config/bundles.php`:

```php
ProductDescriptionAI\Symfony\DeepSeekBundle::class => ['all' => true],
```

Создайте `config/packages/deepseek.yaml`:

```yaml
deepseek:
    api_key: '%env(DEEPSEEK_API_KEY)%'
    model: deepseek-flash
    cache:
        enabled: true
        ttl: 86400
    # system_prompt: 'Write in {language}. {length}'
    # user_prompt: 'Product: {title}'
```

В контроллере или сервисе инжектируйте `ProductDescriptionAI\Core\ProductDescriptionService`.

## Yii 2

Фрагмент `config/web.php`:

```php
'components' => [
    'deepSeek' => [
        'class' => \ProductDescriptionAI\Yii\DeepSeekComponent::class,
        'apiKey' => getenv('DEEPSEEK_API_KEY'),
        'httpClient' => /* PSR-18 client */,
        'requestFactory' => new \Nyholm\Psr7\Factory\Psr17Factory(),
        'streamFactory' => new \Nyholm\Psr7\Factory\Psr17Factory(),
        'cache' => new \ProductDescriptionAI\Yii\Bridge\YiiCacheAdapter(Yii::$app->cache),
        // 'systemPrompt' => 'Write in {language}. {length}',
        // 'userPrompt' => 'Product: {title}',
    ],
],
```



## Кэш и логи

- Кэш по PSR-16 опционален; по умолчанию TTL — 24 часа. Ключ строится из названия и «отпечатка» изображения, без хранения самого файла в ключе.
- Логи по PSR-3: модель, задержка, расход токенов, id запроса. API-ключ и содержимое картинки в лог не попадают.



## Ошибки и повторные запросы

Временные сбои (429, 408, 5xx, сетевые ошибки) повторяются с экспоненциальной задержкой; учитывается заголовок `Retry-After`. Ошибки авторизации и валидации повторно не отправляются.

## Безопасность

- `DEEPSEEK_API_KEY` храните только в переменных окружения или в хранилище секретов.
- Не логируйте промпты с персональными данными клиентов, если на это нет явной необходимости и разрешений.
- Проверяйте размер, целостность и формат изображения на своей стороне до отправки запроса в API.

