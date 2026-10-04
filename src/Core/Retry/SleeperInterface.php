<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Retry;

interface SleeperInterface
{
    public function sleepMicroseconds(int $microseconds): void;
}
