<?php


namespace App\Services;


use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AzureService
{
    public function AzureUploadImage($imgUrl, $path): string
    {
        $fileName = $this->FileNameParsing($imgUrl);

        Storage::disk('azure')->put("/{$path}/{$fileName}", file_get_contents($imgUrl));

        Log::debug(__METHOD__ . ' - data - ' . json_encode([$fileName, $path]));

        $result = "$path/$fileName";

        return $result;
    }

    public function FileNameParsing($imgUrl): string
    {
        $imageUrl = explode('?', $imgUrl);  // 인스타그램 이미지 URL `?` 정리

        return Carbon::now()->timestamp . '_' . preg_replace("/[ #\&\+\-%@=\/\\\:;,\'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", basename($imageUrl[0]));
    }
}
