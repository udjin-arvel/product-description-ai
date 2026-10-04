<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use ProductDescriptionAI\Core\ProductDescriptionService;

/**
 * @method static \ProductDescriptionAI\Core\DTO\GenerateResponse generate(\ProductDescriptionAI\Core\DTO\GenerateRequest $request)
 *
 * @see ProductDescriptionService
 */
final class DeepSeek extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ProductDescriptionService::class;
    }
}
