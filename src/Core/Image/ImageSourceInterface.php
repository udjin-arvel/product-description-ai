<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Image;

interface ImageSourceInterface
{
    /** @return 'base64'|'url' */
    public function type(): string;

    /** Stable fragment for cache key (no full payload). */
    public function cacheKeyFragment(): string;
}
