<?php

namespace App\Services;

use App\Enums\ArticleMediaType;
use App\Models\Instagram\Endpoint;
use App\Models\Instagram\InstagramMedia;
use App\Models\PlatformAccount;
use App\Parsers\InstagramParser;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
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
    protected AzureService $azureService;

    protected PlatformAccount $platformAccount;
    protected string $maxId = '';

    private string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36';

    public function __construct(
        AzureService    $azureService,
        PlatformAccount $platformAccount
    )
    {
        $this->azureService = $azureService;
        $this->platformAccount = $platformAccount;
    }

    /**
     * @param $tag
     * @param $arr
     * @return array
     */
    public function getInstagramByKeyword($tag, $arr): array
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

        $maxId = $arr['data']['recent']['next_max_id'] ?? '';
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
    public function requestInstagramByKeyword(array $headers, string $keyword, string $maxId): array
    {
        $url = str_replace('{tag}', urlencode($keyword), Endpoint::MEDIA_JSON_BY_TAG);
        $url = str_replace('{max_id}', urlencode($maxId), $url);

        $response = Http::withHeaders($headers)->get($url);

        return $this->decodeRawBodyToJson($response->body());
    }

    public function requestInstagramByAccount(array $headers, string $channel, int $count = 100, string $maxId = ''): array
    {
        $index = 0;
        $hasNextPage = true;
        $medias = [];

        $toReturn = [
            'medias' => $medias,
            'maxId' => $maxId,
            'hasNextPage' => $hasNextPage,
        ];


        while ($index < $count && $hasNextPage) {

            $variables = json_encode([
                'id' => $channel,
                'first' => (string)$count,
                'after' => $maxId
            ]);

            $url = str_replace('{variables}', urlencode($variables), Endpoint::ACCOUNT_MEDIAS);

            // $headers = $this->generateHeaders(null, null);

            $response = Http::withHeaders($headers)->get($url);

            $result = $this->decodeRawBodyToJson($response->body());
            $nodes = $result['data']['user']['edge_owner_to_timeline_media']['edges'];

            foreach ($nodes as $mediaArray) {
                if ($index === $count) {
                    return $medias;
                }
                $medias[] = InstagramMedia::create($mediaArray['node']);
                $index++;
            }

            $maxId = $result['data']['user']['edge_owner_to_timeline_media']['page_info']['end_cursor'];
            $hasNextPage = $result['data']['user']['edge_owner_to_timeline_media']['page_info']['has_next_page'];
        }

        return [
            'medias' => $medias,
            'maxId' => $maxId,
            'hasNextPage' => $hasNextPage,
        ];
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
     * @param $session
     * @param $gisToken
     *
     * @return array
     */
    private function generateHeaders($session, $gisToken = null): array
    {
        $headers = [];
        if ($session) {
            $cookies = '';
            foreach ($session as $key => $value) {
                $cookies .= "$key=$value; ";
            }

            $csrf = empty($session['csrftoken']) ? $session['x-csrftoken'] : $session['csrftoken'];

            $headers = [
                'cookie' => $cookies,
                'referer' => Endpoint::BASE_URL . '/',
                'x-csrftoken' => $csrf,
            ];

        }

        if ($this->userAgent) {
            $headers['user-agent'] = $this->userAgent;

            if (!is_null($gisToken)) {
                $headers['x-instagram-gis'] = $gisToken;
            }
        }

        if (empty($headers['x-csrftoken'])) {
            $headers['x-csrftoken'] = md5(uniqid()); // this can be whatever, insta doesn't like an empty value
        }

        return $headers;
    }

    /**
     * @param int $articleId
     * @param string $type
     * @param $node
     * @return array|array[]
     */
    public function getArticleMedias(int $articleId, string $type, $node): array
    {
        if ($type === 'image') {
            return [
                'article_id' => $articleId,
                'type' => ArticleMediaType::IMAGE,
                'storage_url' => $this->azureService->AzureUploadImage($node->getImageStandardResolution()['url'], date('Y') . '/images'),
                'url' => $node->getImageStandardResolution()['url'],
                'width' => $node->getImageStandardResolution()['width'],
                'height' => $node->getImageStandardResolution()['height'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        if ($type === 'video') {
            return [
                [
                    'article_id' => $articleId,
                    'type' => ArticleMediaType::VIDEO,
                    'url' => $node->getVideoStandardResolutionUrl(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ];
        }

        if ($type === 'sidecar') {
            $result = [];
            foreach ($node->getSidecarMedias() as $media) {
                if ($media->getType() === 'image') {
                    array_push($result, [
                        'article_id' => $articleId,
                        'type' => ArticleMediaType::IMAGE,
                        'storage_url' => $this->azureService->AzureUploadImage($node->getImageStandardResolution()['url'], date('Y') . '/images'),
                        'url' => $media->getImageStandardResolution()['url'],
                        'width' => $media->getImageStandardResolution()['width'],
                        'height' => $media->getImageStandardResolution()['height'],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }

                if ($media->getType() === 'video') {
                    array_push($result, [
                        'article_id' => $articleId,
                        'type' => ArticleMediaType::VIDEO,
                        'url' => $media->getVideoStandardResolutionUrl(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }

            return $result;
        }

        return [];
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
