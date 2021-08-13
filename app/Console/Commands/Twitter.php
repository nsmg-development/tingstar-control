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
use App\Services\TwitterService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Twitter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:twitter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '트위터 크롤링';

    protected PlatformEnum $platformEnum;
    protected AzureService $azureService;
    protected Article $article;
    protected ArticleMedia $articleMedia;
    protected Keyword $keyword;
    protected Media $media;
    protected ArticleOwner $articleOwner;
    protected TwitterService $twitterService;
    protected string $nextPageToken;
    protected string $twitterUrl = "https://twitter.com/yunorno/status/";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        PlatformEnum $platformEnum,
        AzureService $azureService,
        Article $article,
        ArticleMedia $articleMedia,
        Keyword $keyword,
        Media $media,
        ArticleOwner $articleOwner,
        TwitterService $twitterService
    )
    {
        parent::__construct();

        $this->platformEnum = $platformEnum;
        $this->azureService = $azureService;
        $this->article = $article;
        $this->articleMedia = $articleMedia;
        $this->keyword = $keyword;
        $this->media = $media;
        $this->articleOwner = $articleOwner;
        $this->twitterService = $twitterService;
    }

    /**
     * Execute the console command.
     *
     */
    //https://api.twitter.com/1.1/search/tweets.json
    public function handle()
    {
        $medias = $this->media->with(['keywords' => function ($query) {
            $query->where('platform', PlatformEnum::TWITTER)->where('state', 1);
        }])->get();

        foreach ($medias as $media) {
            foreach ($media->keywords as $keyword) {
                $keyword = $keyword->keyword;

                // 키워드 정보 가져오기 오류 발생
                if (!$keyword) {
                    Log::error("not found available keywords");
                    return false;
                }

                // do{
                $result = $this->twitterService->getTwitter('골프');
                // 유튜브 데이터 없는 경우 오류 출력
                if (count($result) === 0) {
                    Log::error('no data!');
                    break;
                }
                $this->nextPageToken = $result['nextPageToken'];
                $nodes = $result['medias'];
                foreach ($nodes as $node) {
                    // try {
                    $article = $this->article->where([
                        'media_id' => $media->id,
                        'url' => $this->twitterUrl . $node->getMediaId()
                    ])->first();

                    if (!$article) {
                        $article = $this->article->create([
                            'media_id' => $media->id,
                            'article_owner_id' => $node->getOwnerId(),
                            'platform' => PlatformEnum::TWITTER,
                            'url' => $this->twitterUrl . $node->getMediaId(),
                            'type' => ArticleType::KEYWORD,
                            'keyword' => $keyword,
                            'title' => '',
                            'contents' => $node->getDescription(),
                            'storage_thumbnail_url' => '',
                            'thumbnail_url' => '',
                            'thumbnail_width' => 0,
                            'thumbnail_height' => 0,
                            'state' => 0,
                            'date' => Carbon::parse($node->getDate())->format('Y-m-d H:i:s'),
                        ]);

                        if ($node->getThumbnailUrl()) {
                            $this->articleMedia->create([
                                'article_id' => $article->id,
                                'type' => ArticleMediaType::IMAGE,
                                'storage_url' => $this->azureService->AzureUploadImage($node->getThumbnailUrl(), date('Y') . '/images'),
                                'url' => $node->getThumbnailUrl(),
                                'width' => $node->getThumbnailWidth(),
                                'height' => $node->getThumbnailHeight(),
                            ]);
                        }

                        if ($node->getVideoUrl()) {
                            $this->articleMedia->create([
                                'article_id' => $article->id,
                                'type' => ArticleMediaType::VIDEO,
                                'url' => $node->getVideoUrl(),
                                'width' => $node->getThumbnailWidth(),
                                'height' => $node->getThumbnailHeight(),
                            ]);
                        }
                        // 수집 정보 게시자 저장
                        $this->articleOwner->updateOrCreate(
                            [
                                'id' => (string)$node->getOwnerId(),
                                'platform' => PlatformEnum::TWITTER
                            ],
                            [
                                'name' => $node->getOwnerName(),
                                'url' => $node->getOwnerPageUrl(),
                                'storage_thumbnail_url' => $this->azureService->AzureUploadImage($node->getOwnerImageUrl(), date('Y') . '/images'),
                                'thumbnail_url' => $node->getOwnerImageUrl(),
                                'thumbnail_width' => 0,
                                'thumbnail_height' => 0,
                            ]
                        );
                    }

                    sleep(1);
                    // } catch (\Exception $e) {
                    //     Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                    // }
                }

                $this->info($this->nextPageToken);
                // } while ($this->nextPageToken !== '');
            }
        }
        return true;
    }
}
