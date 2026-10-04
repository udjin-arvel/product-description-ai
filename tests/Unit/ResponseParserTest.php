<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\Exception\MalformedResponseException;
use ProductDescriptionAI\Core\Response\ResponseParser;

final class ResponseParserTest extends TestCase
{
    public function testParsesJsonContent(): void
    {
        $parser = new ResponseParser();
        $decoded = [
            'model' => 'deepseek-flash',
            'choices' => [
                ['message' => ['content' => '{"description":"Great product","keywords":["a","b"]}']],
            ],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
        ];

        $response = $parser->parse($decoded);

        self::assertSame('Great product', $response->description);
        self::assertSame(['a', 'b'], $response->keywords);
        self::assertSame(30, $response->usage?->totalTokens);
    }

    public function testMalformedContentThrows(): void
    {
        $this->expectException(MalformedResponseException::class);
        (new ResponseParser())->parse(['choices' => [['message' => ['content' => 'plain text']]]]);
    }
}
