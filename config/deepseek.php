<?php

declare(strict_types=1);

use ProductDescriptionAI\Core\Config\ClientConfig;

return [
    'api_key' => \env('DEEPSEEK_API_KEY', ''),
    'endpoint' => \env('DEEPSEEK_ENDPOINT', ClientConfig::DEFAULT_ENDPOINT),
    'model' => \env('DEEPSEEK_MODEL', ClientConfig::DEFAULT_MODEL),
    'timeout_seconds' => (int) \env('DEEPSEEK_TIMEOUT', ClientConfig::DEFAULT_TIMEOUT_SECONDS),
    'max_retries' => (int) \env('DEEPSEEK_MAX_RETRIES', ClientConfig::DEFAULT_MAX_RETRIES),
    'cache' => [
        'enabled' => (bool) \env('DEEPSEEK_CACHE_ENABLED', true),
        'ttl' => (int) \env('DEEPSEEK_CACHE_TTL', 86_400),
    ],

    /*
     * null — встроенный промпт пакета.
     * Свой текст может содержать плейсхолдеры:
     * {language}, {style}, {title}, {min_words}, {max_words}, {length}, {extra_instructions}.
     * Если в системном промпте нет {length} или {extra_instructions}, эти строки
     * дописываются в конец, когда в запросе заданы лимит слов или доп. инструкции.
     * Пользовательский промпт по умолчанию: "Generate a product description for the item titled: {title}".
     */
    'system_prompt' => \env('DEEPSEEK_SYSTEM_PROMPT') ?: null,
    'user_prompt' => \env('DEEPSEEK_USER_PROMPT') ?: null,
];
