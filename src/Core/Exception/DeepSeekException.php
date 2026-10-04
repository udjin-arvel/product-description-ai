<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Exception;

use Throwable;

abstract class DeepSeekException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
