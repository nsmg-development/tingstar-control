<?php


namespace App\Services;


use App\Models\PlatformAccount;
use App\Parsers\YoutubeParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YoutubeService
{
    protected PlatformAccount $platformAccount;
    protected string $key = "AIzaSyA7j6AR8E3JcnM-5YqV20HclXhfpR_e7Yo";
    protected string $nextPageToken = '';

    const MEDIA_JSON_BY_TAG = 'https://www.googleapis.com/youtube/v3/search?q={q}&type=video&part=snippet&order=date&maxResults=5&key={key}';

    public function getYoutube(string $keyword): array
    {
        return $this->youtubeParser($keyword);
    }

    public function youtubeParser(string $keyword): array
    {
        $url = str_replace('{q}', urlencode($keyword), static::MEDIA_JSON_BY_TAG);
        $url = str_replace('{key}', $this->key, $url);

        $response = Http::get($url);
        $result = $response->body();
        $arr = $this->decodeRawBodyToJson($result);
        $medias = [];
        $hasNextPage = false;

        $toReturn = [
            'medias' => $medias,
            'hasNextPage' => $hasNextPage,
        ];

        if (empty($arr['pageInfo']['totalResults'])) {
            return $toReturn;
        }

        $sections = $arr['items'];

        if (empty($sections)) {
            return $toReturn;
        }
        foreach ($sections as $section_index => $section) {
            try {
                $medias[] = new YoutubeParser((object)$section);
            } catch (\Exception $e) {
                Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
            }
        }

        $hasNextPage = $arr['nextPageToken'];
        $count = $arr['pageInfo']['totalResults'];
        return [
            'medias' => $medias,
            'count' => $count,
            'nextPageToken' => $hasNextPage,
        ];
    }

    public function decodeRawBodyToJson($rawBody): array
    {
        return json_decode($rawBody, true, 512, JSON_BIGINT_AS_STRING);
    }
}
