<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Laravel\Http;

use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Registers default PSR-18/17 bindings when the host app did not bind them.
 */
final class GuzzleDiscovery
{
    public static function registerBindings(Container $app): void
    {
        if (!$app->bound(ClientInterface::class)) {
            $app->singleton(ClientInterface::class, static fn () => new Client());
        }

        if (!$app->bound(RequestFactoryInterface::class)) {
            $app->singleton(RequestFactoryInterface::class, static fn () => new Psr17Factory());
        }

        if (!$app->bound(StreamFactoryInterface::class)) {
            $app->singleton(StreamFactoryInterface::class, static fn () => new Psr17Factory());
        }
    }
}
