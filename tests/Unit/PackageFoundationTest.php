<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PackageFoundationTest extends TestCase
{
    public function testAutoloadWorks(): void
    {
        self::assertTrue(\class_exists(\ProductDescriptionAI\Core\Config\ClientConfig::class));
    }
}
