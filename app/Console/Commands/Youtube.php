<?php

namespace App\Console\Commands;

use App\Enums\ArticleMediaType;
use App\Enums\ArticleType;
use App\Enums\PlatformEnum;
use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\Keyword;
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
    protected $signature = 'scrap:youtube';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '유튜브 크롤링';

    protected PlatformEnum $platformEnum;
    protected YoutubeService $youtubeService;
    protected Article $article;
    protected ArticleMedia $articleMedia;
    protected Keyword $keyword;
    protected string $nextPageToken;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        PlatformEnum $platformEnum,
        YoutubeService $youtubeService,
        Article $article,
        ArticleMedia $articleMedia,
        Keyword $keyword
    )
    {
        parent::__construct();

        $this->platformEnum = $platformEnum;
        $this->youtubeService = $youtubeService;
        $this->article = $article;
        $this->articleMedia = $articleMedia;
        $this->keyword = $keyword;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $keywords = $this->keyword->active(PlatformEnum::YOUTUBE)->get();

        // 키워드 정보 가져오기 오류 발생
        if (!$keywords) {
            Log::error("not found available keywords");
            return false;
        }

        foreach ($keywords as $keyword) {
            $keyword = $keyword->keyword;

            do{
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
                        $article = $this->article->where([
                            'media_id' => 1,
                            'url' => $node->getUrl()
                        ])->first();

                        if (!$article) {
                            $article = $this->article->create([
                                'media_id' => 1,
                                'platform' => PlatformEnum::YOUTUBE,
                                'url' => $node->getUrl(),
                                'type' => ArticleType::KEYWORD,
                                'keyword' => $keyword,
                                'title' => $node->getTitle(),
                                'contents' => $node->getDescription(),
                                'thumbnail_url' => $node->getThumbnailsUrl(),
                                'thumbnail_width' => $node->getThumbnailWidth(),
                                'thumbnail_height' => $node->getThumbnailHeight(),
                                'state' => 0,
                                'date' => Carbon::parse($node->getCreatedTime())->format('Y-m-d H:i:s'),
                            ]);

                            if ($node->getUrl()) {
                                $this->articleMedia->create([
                                    'article_id' => $article->id,
                                    'type' => ArticleMediaType::VIDEO,
                                    'url' => $node->getUrl(),
                                    'width' => 0,
                                    'height' => 0,
                                ]);
                            }
                        }

                        sleep(1);
                    } catch (\Exception $e) {
                        Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                    }
                }

                $this->info($this->nextPageToken);
            } while ($this->nextPageToken !== '');
        }

        return true;
    }
}
