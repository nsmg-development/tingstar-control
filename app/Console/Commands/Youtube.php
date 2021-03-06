<?php

namespace App\Console\Commands;

use App\Enums\ArticleMediaType;
use App\Enums\ArticleType;
use App\Enums\PlatformEnum;
use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\ArticleOwner;
use App\Models\Keyword;
use App\Models\Media;
use App\Services\AzureService;
use App\Services\YoutubeService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Youtube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:youtube:keyword';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '유튜브 크롤링(키워드)';

    protected PlatformEnum $platformEnum;
    protected YoutubeService $youtubeService;
    protected AzureService $azureService;
    protected Article $article;
    protected ArticleMedia $articleMedia;
    protected Keyword $keyword;
    protected Media $media;
    protected ArticleOwner $articleOwner;
    protected string $nextPageToken;
    protected string $storageBaseUrl = "https://chuncheon.blob.core.windows.net/chuncheon/";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        PlatformEnum   $platformEnum,
        YoutubeService $youtubeService,
        AzureService   $azureService,
        Article        $article,
        ArticleMedia   $articleMedia,
        Keyword        $keyword,
        Media          $media,
        ArticleOwner   $articleOwner
    )
    {
        parent::__construct();

        $this->platformEnum = $platformEnum;
        $this->youtubeService = $youtubeService;
        $this->azureService = $azureService;
        $this->article = $article;
        $this->articleMedia = $articleMedia;
        $this->keyword = $keyword;
        $this->media = $media;
        $this->articleOwner = $articleOwner;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $medias = $this->media->with(['keywords' => function ($query) {
            $query->where('platform', PlatformEnum::YOUTUBE)->where('state', 1);
        }])->get();

        foreach ($medias as $media) {
            foreach ($media->keywords as $keyword) {
                $keyword = $keyword->keyword;

                // 키워드 정보 가져오기 오류 발생
                if (!$keyword) {
                    Log::error("not found available keywords");
                    return false;
                }

                $lastRow = $this->article->where('media_id', $media->id)->where('platform', PlatformEnum::YOUTUBE)->orderBy('id')->first();

                $i = 0;
                do {
                    $result = $this->youtubeService->getYoutube($keyword);

                    // 유튜브 데이터 없는 경우 오류 출력
                    if (count($result) === 0) {
                        Log::error('no data!');
                        break;
                    }

                    $this->nextPageToken = $result['nextPageToken'];
                    $nodes = $result['medias'];

                    foreach ($nodes as $node) {
                        try {

                            if($lastRow) {
                                if ($lastRow->media_id === $media->id && $lastRow->keyword === $keyword && $lastRow->url === $node->getUrl()) {
                                    $this->info('stop!!!');
                                    break 2;
                                }
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

                                if($node->getThumbnailsUrl()) {
                                    $thumbnail = $this->azureService->AzureUploadImage($node->getThumbnailsUrl(), date('Y') . '/images');
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

                                $article = $this->article->insertGetId([
                                    'id' => $id,
                                    'media_id' => $media->id,
                                    'article_owner_id' => $node->getOwnerId(),
                                    'platform' => PlatformEnum::YOUTUBE,
                                    'url' => $node->getUrl(),
                                    'type' => ArticleType::KEYWORD,
                                    'keyword' => $keyword,
                                    'title' => $node->getTitle(),
                                    'contents' => $node->getDescription(),
                                    'storage_thumbnail_url' => $thumbnail,
                                    'thumbnail_url' => $node->getThumbnailsUrl() ?? null,
                                    'thumbnail_width' => $width,
                                    'thumbnail_height' => $height,
                                    'mime' => $mime,
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
                    $this->info($keyword);
                    $this->info($this->nextPageToken);
                } while ($i < 100);
            }
        }
        return true;
    }
}
