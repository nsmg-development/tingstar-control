<?php

namespace App\Services;

use App\Models\PlatformAccount;
use App\Parsers\InstagramParser;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InstagramScraper\Exception\InstagramAuthException;
use InstagramScraper\Exception\InstagramChallengeRecaptchaException;
use InstagramScraper\Exception\InstagramChallengeSubmitPhoneNumberException;
use InstagramScraper\Exception\InstagramException;
use InstagramScraper\Instagram;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\SimpleCache\InvalidArgumentException;

class InstagramService
{
    protected PlatformAccount $platformAccount;
    protected string $maxId = '';
    const MEDIA_JSON_BY_TAG = 'https://www.instagram.com/explore/tags/{tag}/?__a=1&max_id={max_id}';

    public function __construct(PlatformAccount $platformAccount)
    {
        $this->platformAccount = $platformAccount;
    }

    /**
     * @param $tag
     * @param $arr
     * @return array
     */
    public function getInstagram($tag, $arr): array
    {
        // 현재 미사용
        if (array_key_exists('graphql', $arr)) {
            return $this->instagramParserVer1($tag, $arr);
        }

        if (array_key_exists('data', $arr)) {
            return $this->instagramParserVer2($tag, $arr);
        }

        // 파싱 오류 팀즈로 노티피케이션 전달
        $this->parsingError($tag, $arr);

        return [];
    }

    /**
     * @param $tag
     * @param $arr
     * @return string[]
     */
    private function instagramParserVer1($tag, $arr): array
    {
        return ['ver1'];
    }

    /**
     * @param $tag
     * @param $arr
     * @return array
     */
    private function instagramParserVer2($tag, $arr): array
    {
        $medias = [];
        $hasNextPage = false;

        $toReturn = [
            'medias' => $medias,
            'maxId' => $this->maxId,
            'hasNextPage' => $hasNextPage,
        ];


        if (empty($arr['data']['media_count'])) {
            return $toReturn;
        }

        // $sections = $arr['data']['top']['sections'];
        $sections = $arr['data']['recent']['sections'];

        if (empty($sections)) {
            return $toReturn;
        }

        foreach ($sections as $section_index => $section) {
            if ($section['feed_type'] !== 'media') {
                continue;
            }
            // echo 'section index:'.$section_index .PHP_EOL;
            foreach ($section['layout_content']['medias'] as $media_index => $media) {
                // echo 'media index:'.$media_index .PHP_EOL;

                // 캡션이 비어있는 경우 수집안함.
                if (empty($media['media']['caption']['text'])) {
                    continue;
                }

                $captionTextRegex = preg_match("/{$tag}/", $media['media']['caption']['text']);
                if (empty($captionTextRegex)) {
                    continue;
                }

                try {
                    $medias[] = new InstagramParser((object)$media['media']);
                    // $medias[] = $media['media'];
                } catch (\Exception $e) {
                    Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                }
            }
        }

        $maxId = $arr['data']['recent']['next_max_id'];
        $hasNextPage = $arr['data']['recent']['more_available'];
        $count = $arr['data']['media_count'];

        return [
            'medias' => $medias,
            'count' => $count,
            'maxId' => $maxId,
            'hasNextPage' => $hasNextPage,
        ];
    }

    /**
     * @param string $platform
     * @param int $use_count
     * @return mixed
     */
    public function getPlatformAccount(string $platform, int $use_count = 0)
    {
        return $this->platformAccount->active()
            ->whereHas('platform', function ($query) use ($platform) {
                $query->where('platform', $platform);
            })
            ->where('use_count', '<=', $use_count)
            ->first();
    }

    /**
     * @param array $headers
     * @param string $keyword
     * @param string $maxId
     * @return array
     */
    public function requestInstagram(array $headers, string $keyword, string $maxId): array
    {
        $url = str_replace('{tag}', urlencode($keyword), static::MEDIA_JSON_BY_TAG);
        $url = str_replace('{max_id}', urlencode($maxId), $url);

        $response = Http::withHeaders($headers)->get($url);

        return $this->decodeRawBodyToJson($response->body());
    }

    /**
     * @param string $login_id
     * @param string $login_password
     * @return array
     */
    public function initInstagram(string $login_id, string $login_password): array
    {
        $client = new Client();
        $instagram = Instagram::withCredentials($client, $login_id, $login_password, new Psr16Adapter('Files'));
        $headers = [];

        try {
            $headers = $instagram->login();
        } catch (InstagramAuthException $e) {
            Log::error("Instagram Auth Error: login_id=$login_id, login_password=$login_password");
        } catch (InstagramChallengeRecaptchaException $e) {
            Log::error("Instagram Recaptcha Error: login_id=$login_id, login_password=$login_password");
        } catch (InstagramChallengeSubmitPhoneNumberException | InstagramException | InvalidArgumentException $e) {
            Log::error('Instagram Etc Error');
        }

        return $headers;
    }

    /**
     * @param $rawBody
     * @return mixed
     */
    public function decodeRawBodyToJson($rawBody): array
    {
        return json_decode($rawBody, true, 512, JSON_BIGINT_AS_STRING);
    }

    /**
     * @param string $tag
     * @param array $arr
     */
    private function parsingError(string $tag, array $arr): void
    {
        Log::error("Parsing Error!: tag={$tag}");
    }
}
