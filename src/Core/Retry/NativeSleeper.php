<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Retry;

final class NativeSleeper implements SleeperInterface
{
    public function sleepMicroseconds(int $microseconds): void
    {
        if ($microseconds > 0) {
            \usleep($microseconds);
        }
    }
}
