<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Support;

use Psr\Http\Message\StreamInterface;

final class MockStream implements StreamInterface
{
    private int $pos = 0;

    public function __construct(private readonly string $content)
    {
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): int
    {
        return \strlen($this->content);
    }

    public function tell(): int
    {
        return $this->pos;
    }

    public function eof(): bool
    {
        return $this->pos >= \strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->pos = (int) $offset;
    }

    public function rewind(): void
    {
        $this->pos = 0;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        return 0;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        $chunk = \substr($this->content, $this->pos, $length);
        $this->pos += \strlen($chunk);

        return $chunk;
    }

    public function getContents(): string
    {
        $rest = \substr($this->content, $this->pos);
        $this->pos = \strlen($this->content);

        return $rest;
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}
