# Реализация PHP-расширения для генерации описаний товаров через DeepSeek API (OpenAI-совместимый)

Создание единого пакета, работающего в Laravel, Symfony и Yii, — задача вполне решаемая, но требует продуманной архитектуры. Ниже — практический план и ключевые технические решения.

---

## 1. Структура пакета (framework-agnostic ядро)

Рекомендую строить пакет по принципу «ядро + адаптеры»: общая логика выносится в независимые классы, а для каждого фреймворка создаётся тонкая обёртка (Service Provider / Bundle / Component).

```
src/
├── Core/
│   ├── DeepSeekClient.php        # HTTP-клиент DeepSeek
│   ├── ProductDescriptionService.php  # Бизнес-логика генерации
│   └── DTO/
│       ├── GenerateRequest.php
│       └── GenerateResponse.php
├── Laravel/
│   ├── DeepSeekServiceProvider.php
│   └── Facades/DeepSeek.php
├── Symfony/
│   ├── DeepSeekBundle.php
│   └── DependencyInjection/
└── Yii/
    └── DeepSeekComponent.php
```

**Ключевые требования к ядру:**

- PHP 8.1+ (DeepSeek API требует ext-json, ext-curl) 
- Зависимость только от PSR-18 HTTP Client (Guzzle или Symfony HttpClient)
- Никаких обращений к глобальным функциям фреймворков (`config()`, `Yii::app()`) в ядре

---



## 2. Работа с Vision API DeepSeek

DeepSeek поддерживает изображения через модель `deepseek-flash`.

### Формат запроса (OpenAI-совместимый)

```php
// Ядро: ProductDescriptionService.php
public function generate(string $title, string $imageData, string $format = 'jpeg'): string
{
    $payload = [
        'model' => 'deepseek-flash',
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Сгенерируй описание товара. Заголовок: {$title}"
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => "data:image/{$format};base64,{$imageData}"
                        ]
                    ]
                ]
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2048
    ];

    return $this->httpClient->post('https://api.deepseek.com/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => $payload,
    ])->getBody()->getContents();
}
```

**Способы передачи изображения** :


| Способ        | Ограничение                        | Когда использовать                   |
| ------------- | ---------------------------------- | ------------------------------------ |
| Base64 inline | 48 MiB body limit                  | Локальные файлы, одноразовые запросы |
| Внешний URL   | URL ≤ 8192 символов, файл ≤ 32 MiB | Изображения на CDN/S3                |
| Files API     | Файл ≤ 64 MiB                      | Повторное использование изображений  |


**Параметр** `detail` управляет обработкой: `low` (512×512, дешевле), `high`/`original` (полное разрешение), `auto` .

---



## 3. Адаптеры для фреймворков



### Laravel

**Service Provider** регистрирует клиент как singleton и публикует конфиг :

```php
namespace Vendor\DeepSeek\Laravel;

use Illuminate\Support\ServiceProvider;
use Vendor\DeepSeek\Core\DeepSeekClient;
use Vendor\DeepSeek\Core\ProductDescriptionService;

class DeepSeekServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/deepseek.php', 'deepseek');

        $this->app->singleton(DeepSeekClient::class, function ($app) {
            return new DeepSeekClient(
                apiKey: config('deepseek.api_key'),
                httpClient: $app->make('Http\Client') // Laravel HTTP Client
            );
        });

        $this->app->singleton(ProductDescriptionService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/deepseek.php' => config_path('deepseek.php'),
        ], 'deepseek-config');
    }
}
```

**Facade** для удобного вызова:

```php
namespace Vendor\DeepSeek\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class DeepSeek extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Vendor\DeepSeek\Core\ProductDescriptionService::class;
    }
}

// Использование: DeepSeek::generate($title, $imageData);
```



### Symfony

**Bundle** с автоконфигурацией через `AbstractBundle` (Symfony 6.1+) :

```php
namespace Vendor\DeepSeek\Symfony;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class DeepSeekBundle extends AbstractBundle
{
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        $container->services()
            ->set(DeepSeekClient::class)
                ->args([$config['api_key'], service('http_client')])
            ->set(ProductDescriptionService::class)
                ->args([service(DeepSeekClient::class)])
            ->public();
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('framework', [
            'http_client' => ['enabled' => true],
        ]);
    }
}
```

**Конфиг** `config/packages/deepseek.yaml`:

```yaml
deepseek:
    api_key: '%env(DEEPSEEK_API_KEY)%'
```



### Yii

**Application Component** — стандартный паттерн для Yii 2 :

```php
namespace Vendor\DeepSeek\Yii;

use yii\base\Component;
use Vendor\DeepSeek\Core\DeepSeekClient;
use Vendor\DeepSeek\Core\ProductDescriptionService;

class DeepSeekComponent extends Component
{
    public string $apiKey = '';

    private ?ProductDescriptionService $service = null;

    public function init(): void
    {
        parent::init();
        $client = new DeepSeekClient($this->apiKey);
        $this->service = new ProductDescriptionService($client);
    }

    public function generate(string $title, string $imageData, string $format = 'jpeg'): string
    {
        return $this->service->generate($title, $imageData, $format);
    }
}
```

**Конфиг приложения:**

```php
// config/web.php
'components' => [
    'deepSeek' => [
        'class' => \Vendor\DeepSeek\Yii\DeepSeekComponent::class,
        'apiKey' => getenv('DEEPSEEK_API_KEY'),
    ],
],
```

---



## 4. Промпт-инжиниринг для генерации описаний

Базовый промпт для качественного результата:

```php
$systemPrompt = <<<PROMPT
Ты — копирайтер интернет-магазина. По заголовку и изображению товара создай продающее описание.

Требования:
- Объём: 150–300 слов
- Структура: короткий цепляющий абзац → ключевые характеристики → область применения → призыв к действию
- Стиль: нейтрально-уверенный, без излишней эмоциональности
- Если на изображении видны детали (цвет, материал, комплектация) — обязательно упомяни их
- Верни ответ в формате JSON: {"description": "...", "keywords": ["...", "..."]}
PROMPT;
```

Если нужен структурированный вывод, используйте `response_format: 'json_object'`, но в промпте обязательно должно быть слово **«json»** — иначе API вернёт ошибку .

---



## 5. Обработка ошибок и ретраи

DeepSeek API может возвращать ошибки при перегрузке. Рекомендуется обёртка с экспоненциальной задержкой:

```php
class DeepSeekClient
{
    public function __construct(
        private string $apiKey,
        private ?ClientInterface $httpClient = null,
        private int $maxRetries = 3
    ) {}

    public function chat(array $payload): array
    {
        $attempt = 0;
        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->httpClient->request('POST', self::API_URL, [
                    'headers' => ['Authorization' => "Bearer {$this->apiKey}"],
                    'json' => $payload,
                    'timeout' => 60,
                ]);
                return json_decode($response->getBody(), true);
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt === $this->maxRetries) throw $e;
                usleep(2 ** $attempt * 500_000); // 1s, 2s, 4s...
            }
        }
    }
}
```

---



## 6. Публикация и установка

**composer.json** пакета:

```json
{
    "name": "vendor/deepseek-product-description",
    "type": "library",
    "require": {
        "php": "^8.1",
        "ext-json": "*",
        "ext-curl": "*",
        "psr/http-client": "^1.0"
    },
    "require-dev": {
        "guzzlehttp/guzzle": "^7.0",
        "symfony/http-client": "^6.0|^7.0",
        "laravel/framework": "^10.0|^11.0|^12.0",
        "yiisoft/yii2": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "Vendor\\DeepSeek\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": ["Vendor\\DeepSeek\\Laravel\\DeepSeekServiceProvider"],
            "aliases": {"DeepSeek": "Vendor\\DeepSeek\\Laravel\\Facades\\DeepSeek"}
        }
    }
}
```

Установка в любом проекте:

```bash
composer require vendor/deepseek-product-description
```

---



## 7. Рекомендации по архитектуре

1. **Не привязывайтесь к Guzzle жёстко** — используйте PSR-18, тогда Symfony HttpClient и Laravel HTTP Client подойдут без изменений .
2. **Кэшируйте результаты** — если один и тот же товар генерируется повторно, сохраните ответ в кэш фреймворка (Laravel Cache, Symfony Cache, Yii Cache) с TTL 24 часа.
3. **Валидируйте изображения** на стороне PHP до отправки (формат, размер, MIME) — не полагайтесь только на API.
4. **Логируйте токены** — DeepSeek возвращает `usage` в ответе, это поможет контролировать расходы.

