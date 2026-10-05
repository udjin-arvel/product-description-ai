<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Exception\DeepSeekException;
use ProductDescriptionAI\Core\Exception\ValidationException;
use ProductDescriptionAI\Core\Image\Base64Image;
use ProductDescriptionAI\Laravel\Facades\DeepSeek;

final class GenerateController extends Controller
{
    private const MAX_IMAGE_BYTES = 10 * 1024 * 1024;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:1', 'max:500'],
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'min_words' => ['nullable', 'integer', 'min:1', 'max:' . GenerateRequest::MAX_WORD_COUNT],
            'max_words' => ['nullable', 'integer', 'min:1', 'max:' . GenerateRequest::MAX_WORD_COUNT],
        ]);

        $minWords = isset($validated['min_words']) ? (int) $validated['min_words'] : null;
        $maxWords = isset($validated['max_words']) ? (int) $validated['max_words'] : null;
        if ($minWords !== null && $maxWords !== null && $minWords > $maxWords) {
            return response()->json(['message' => 'Минимум слов не может превышать максимум.'], 422);
        }

        $file = $request->file('image');
        if ($file === null) {
            return response()->json(['message' => 'Изображение обязательно.'], 422);
        }

        if ($file->getSize() > self::MAX_IMAGE_BYTES) {
            return response()->json(['message' => 'Размер изображения не должен превышать 10 МБ.'], 422);
        }

        try {
            $extension = $file->extension() ?: 'jpeg';
            $response = DeepSeek::generate(new GenerateRequest(
                title: $validated['title'],
                image: new Base64Image(base64_encode($file->getContent()), $extension),
                minWords: $minWords,
                maxWords: $maxWords,
            ));

            return response()->json($response->toArray());
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (DeepSeekException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}
