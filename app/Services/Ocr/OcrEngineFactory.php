<?php

namespace App\Services\Ocr;

use App\Services\Ocr\Contracts\OcrEngineInterface;

class OcrEngineFactory
{
    public static function create(): OcrEngineInterface
    {
        return new GeminiEngine();
    }
}
