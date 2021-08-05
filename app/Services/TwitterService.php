<?php


namespace App\Services;

use App\Parsers\TwitterParser;
use Illuminate\Support\Facades\Log;
use Atymic\Twitter\Twitter as TwitterContract;
use Twitter;

class TwitterService
{
    public function getTwitter(string $keyword): array
    {
        return $this->twitterParser($keyword);
    }

    public function twitterParser(string $keyword): array
    {
        $params = [
            'tweet.fields' => 'author_id,created_at,attachments',
            'media.fields' => 'preview_image_url',
            'user.fields' => 'profile_image_url',
            'expansions' => 'author_id',
            TwitterContract::KEY_RESPONSE_FORMAT => TwitterContract::RESPONSE_FORMAT_JSON,
        ];

        $arr = $this->decodeRawBodyToJson(Twitter::searchRecent($keyword . ' -is:retweet', $params));

        $medias = [];
        $hasNextPage = false;

        $toReturn = [
            'medias' => $medias,
            'hasNextPage' => $hasNextPage,
        ];
        if (empty($arr['meta']['result_count'])) {
            return $toReturn;
        }

        $sections = $arr['data'];
        $sectionUsers = $arr['includes']['users'];
        if (empty($sections)) {
            return $toReturn;
        }
        foreach ($sections as $section_index => $section) {
            foreach ($sectionUsers as $user) {
                try {
                    $medias[] = new TwitterParser((object)$section, $user);
                } catch (\Exception $e) {
                    Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                }
            }
        }
        $hasNextPage = $arr['meta']['next_token'];
        $count = $arr['meta']['result_count'];
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
