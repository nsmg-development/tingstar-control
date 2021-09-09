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
use App\Services\YoutubeChannelService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class YoutubeChannel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:youtube:channel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '유튜브 크롤링(채널)';

    protected PlatformEnum $platformEnum;
    protected YoutubeChannelService $youtubeChannelService;
    protected AzureService $azureService;
    protected Article $article;
    protected ArticleMedia $articleMedia;
    protected Channel $channel;
    protected Media $media;
    protected ArticleOwner $articleOwner;
    protected string $nextPageToken;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        PlatformEnum          $platformEnum,
        YoutubeChannelService $youtubeChannelService,
        AzureService          $azureService,
        Article               $article,
        ArticleMedia          $articleMedia,
        Channel               $channel,
        Media                 $media,
        ArticleOwner          $articleOwner
    )
    {
        parent::__construct();

        $this->platformEnum = $platformEnum;
        $this->youtubeChannelService = $youtubeChannelService;
        $this->azureService = $azureService;
        $this->article = $article;
        $this->articleMedia = $articleMedia;
        $this->channel = $channel;
        $this->media = $media;
        $this->articleOwner = $articleOwner;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $medias = $this->media->with(['channels' => function ($query) {
            $query->where('platform', PlatformEnum::YOUTUBE)->where('state', 1);
        }])->get();

        foreach ($medias as $media) {
            foreach ($media->channels as $channel) {
                $channel = $channel->channel;

                // 키워드 정보 가져오기 오류 발생
                if (!$channel) {
                    Log::error("not found available channels");
                    return false;
                }

                $lastRow = $this->article->where('media_id', $media->id)->where('platform', PlatformEnum::YOUTUBE)->orderBy('id')->first();

                $i = 0;
                do {
                    $result = $this->youtubeChannelService->getYoutube($channel);

                    // 유튜브 데이터 없는 경우 오류 출력
                    if (count($result) === 0) {
                        Log::error('no data!');
                        break;
                    }

                    $this->nextPageToken = $result['nextPageToken'];
                    $nodes = $result['medias'];

                    foreach ($nodes as $node) {
                        try {

                            if ($lastRow->media_id === $media->id && $lastRow->channel === $channel && $lastRow->url === $node->getUrl()) {
                                $this->info('stop!!!');
                                break 2;
                            }

                            $article = $this->article->where([
                                'media_id' => $media->id,
                                'url' => $node->getUrl()
                            ])->first();

                            if (!$article) {
                                $date = Carbon::parse($node->getCreatedTime())->format('Y-m-d H:i:s');
                                $id = Carbon::parse($date)->getTimestamp() * -1;
                                $has_media = false;

                                if ($node->getUrl()) {
                                    $has_media = true;
                                }

                                $article = $this->article->create([
                                    'id' => $id,
                                    'media_id' => $media->id,
                                    'article_owner_id' => $node->getOwnerId(),
                                    'platform' => PlatformEnum::YOUTUBE,
                                    'url' => $node->getUrl(),
                                    'type' => ArticleType::CHANNEL,
                                    'channel' => $channel,
                                    'title' => $node->getTitle(),
                                    'contents' => $node->getDescription(),
                                    'storage_thumbnail_url' => $this->azureService->AzureUploadImage($node->getThumbnailsUrl(), date('Y') . '/images'),
                                    'thumbnail_url' => $node->getThumbnailsUrl(),
                                    'thumbnail_width' => $node->getThumbnailWidth(),
                                    'thumbnail_height' => $node->getThumbnailHeight(),
                                    'state' => 0,
                                    'date' => $date,
                                    'has_media' => $has_media
                                ]);

                                if ($node->getUrl()) {
                                    $this->articleMedia->create([
                                        'article_id' => $id,
                                        'type' => ArticleMediaType::VIDEO,
                                        'url' => $node->getUrl(),
                                        'width' => 0,
                                        'height' => 0,
                                    ]);
                                }
                                // 수집 정보 게시자 저장
                                $this->articleOwner->updateOrCreate(
                                    [
                                        'id' => (string)$node->getOwnerId(),
                                        'platform' => PlatformEnum::YOUTUBE
                                    ],
                                    [
                                        'name' => $node->getOwnerName()
                                    ]
                                );
                            }
                            $i++;
                            sleep(1);
                        } catch (\Exception $e) {
                            Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                        }
                    }
                    if ($this->nextPageToken == '') {
                        break 2;
                    }
                    $this->info($i . ':' . $node->getUrl());
                    $this->info($channel);
                    $this->info($this->nextPageToken);
                } while (!$this->nextPageToken);
            }
        }
        return true;
    }
}
