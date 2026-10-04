<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Support;

use ProductDescriptionAI\Core\Retry\SleeperInterface;

final class FakeSleeper implements SleeperInterface
{
    /** @var list<int> */
    public array $sleptMicroseconds = [];

    public function sleepMicroseconds(int $microseconds): void
    {
        $this->sleptMicroseconds[] = $microseconds;
    }
}
