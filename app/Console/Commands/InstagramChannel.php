<?php

namespace App\Console\Commands;

use App\Enums\ArticleMediaType;
use App\Enums\ArticleType;
use App\Enums\PlatformEnum;
use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\ArticleOwner;
use App\Models\Channel;
use App\Models\Media;
use App\Services\AzureService;
use App\Services\InstagramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InstagramChannel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:instagram:channel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '인스타그램 크롤링(채널별)';

    protected InstagramService $instagramService;
    protected AzureService $azureService;

    protected Channel $channel;
    protected Article $article;
    protected ArticleMedia $articleMedia;
    protected ArticleOwner $articleOwner;
    protected Media $media;
    protected string $maxId = '';
    protected string $storageBaseUrl = "https://chuncheon.blob.core.windows.net/chuncheon/";
    protected string $baseUrl = "https://www.instagram.com/";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        InstagramService $instagramService,
        AzureService     $azureService,
        Channel          $channel,
        Article          $article,
        ArticleMedia     $articleMedia,
        ArticleOwner     $articleOwner,
        Media            $media
    )
    {
        parent::__construct();

        $this->instagramService = $instagramService;
        $this->azureService = $azureService;
        $this->channel = $channel;
        $this->article = $article;
        $this->articleMedia = $articleMedia;
        $this->articleOwner = $articleOwner;
        $this->media = $media;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 인스타그램 로그인 계정 DB 참조
        $platformAccount = $this->instagramService->getPlatformAccount(PlatformEnum::INSTAGRAM, 100);
        if (!$platformAccount) {
            Log::error("not found available platform account");
            return false;
        }

        $login_id = $platformAccount->login_id;
        $login_password = $platformAccount->login_password;

        // 스크래핑 헤더 캐싱
        $headers = $this->instagramService->initInstagram($login_id, $login_password);

        // channel 정보 DB에서 가져오기
        // $channels = $this->channel->active(PlatformEnum::INSTAGRAM)->get();

        $medias = $this->media->with(['channels' => function ($query) {
            $query->where('platform', PlatformEnum::INSTAGRAM)->where('state', 1);
        }])->get();

        foreach ($medias as $media) {
            // 반복하여 스크래핑 처리
            foreach ($media->channels as $channel) {
                $channel = $channel->channel;
                // 채널 정보 가져오기 오류 발생
                if (!$channel) {
                    Log::error("not found available channels");
                    return false;
                }

                $lastRow = $this->article->where('media_id', $media->id)->where('platform', PlatformEnum::INSTAGRAM)->orderBy('id')->first();

                $i = 0;
                do {
                    $result = $this->instagramService->requestInstagramByAccount($headers, $channel, 100, $this->maxId);

                    if (count($result) === 0) {
                        Log::error('no data!');
                        break;
                    }

                    $this->maxId = $result['maxId'];
                    $nodes = $result['medias'];

                    foreach ($nodes as $node) {
                        // ArticleMediaType::getValueByName($node->getSidecarMedias()[0]->getType());
                        try {

                            if($lastRow) {
                                if ($lastRow->media_id === $media->id && $lastRow->channel === $channel && $lastRow->url === $node->getLink()) {
                                    $this->info('stop!!!');
                                    break 2;
                                }
                            }

                            $article = $this->article->where([
                                'media_id' => $media->id,
                                'url' => $node->getLink()
                            ])->first();

                            if (!$article) {
                                $date = Carbon::parse($node->getCreatedTime())->format('Y-m-d H:i:s');
                                $id = Carbon::parse($date)->getTimestamp() * -1;
                                $has_media = false;

                                if (ArticleMediaType::isValidValue($node->getType()) || $node->getType() === 'sidecar') {
                                    $has_media = true;
                                }

                                if($node->getImageThumbnail()['url']) {
                                    $thumbnail = $this->azureService->AzureUploadImage($node->getImageThumbnail()['url'], date('Y') . '/images');
                                    $size = getimagesize($this->storageBaseUrl . $thumbnail);
                                    $width = $size[0];
                                    $height = $size[1];
                                    $mime = $size['mime'];
                                } else {
                                    $thumbnail = null;
                                    $width = 0;
                                    $height = 0;
                                    $mime = null;
                                }

                                // 수집 정보 저장
                                $article = $this->article->create([
                                    'id' => $id,
                                    'media_id' => $media->id,
                                    'platform' => PlatformEnum::INSTAGRAM,
                                    'article_owner_id' => $node->getOwnerId(),
                                    'url' => $node->getLink(),
                                    'type' => ArticleType::CHANNEL,
                                    'channel' => $channel,
                                    'title' => '',
                                    'contents' => $node->getCaption(),
                                    'storage_thumbnail_url' => $thumbnail,
                                    'thumbnail_url' => $node->getImageThumbnail()['url'] ?? null,
                                    'thumbnail_width' => $width,
                                    'thumbnail_height' => $height,
                                    'mime' => $mime,
                                    'hashtag' => $node->getHashTag(),
                                    'state' => 0,
                                    'date' => $date,
                                    'has_media' => $has_media
                                ]);

                                // if ($node->getProfilePicUrl()) {
                                //     $thumbnail = $this->azureService->AzureUploadImage($node->getProfilePicUrl(), date('Y') . '/images');
                                //     $size = getimagesize($this->storageBaseUrl . $thumbnail);
                                //     $width = $size[0];
                                //     $height = $size[1];
                                // }

                                // 수집 정보 게시자 저장
                                $this->articleOwner->updateOrCreate(
                                    [
                                        'id' => (string)$node->getOwnerId(),
                                        'platform' => PlatformEnum::INSTAGRAM
                                    ],
                                    [
                                        'name' => $node->getOwner()['username'],
                                        'url' => $this->baseUrl . $node->getOwner()['username'],
                                        // 'storage_thumbnail_url' => $thumbnail ?? null,
                                        // 'thumbnail_url' => $node->getProfilePicUrl() ?? null,
                                        // 'thumbnail_width' => $width ?? 0,
                                        // 'thumbnail_height' => $height ?? 0,
                                    ]
                                );

                                $articleMedias = $this->instagramService->getArticleMedias($id, $node->getType(), $node);
                                if (count($articleMedias) > 0) {
                                    $this->articleMedia->insert($articleMedias);
                                }
                            }
                            $i++;
                            sleep(1);
                        } catch (\Exception $e) {
                            Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                        }
                    }
                    if ($this->maxId == '') {
                        break 2;
                    }
                    $this->info($i . ':' . $node->getLink());
                    $this->info($channel);
                    $this->info($this->maxId);
                } while ($i < 100);
            }
        }
        return true;
    }
}
