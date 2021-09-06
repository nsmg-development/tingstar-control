<?php

namespace App\Console\Commands;

use App\Enums\ArticleType;
use App\Enums\PlatformEnum;
use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\ArticleOwner;
use App\Models\Keyword;
use App\Models\Media;
use App\Services\AzureService;
use App\Services\NaverBlogService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NaverBlog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:blog:naver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '네이버 블로그 크롤링';

    protected PlatformEnum $platformEnum;
    protected AzureService $azureService;
    protected Article $article;
    protected ArticleMedia $articleMedia;
    protected Keyword $keyword;
    protected Media $media;
    protected ArticleOwner $articleOwner;
    protected string $nextPageToken;
    protected NaverBlogService $naverBlogService;

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
        NaverBlogService $naverBlogService
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
        $this->naverBlogService = $naverBlogService;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $medias = $this->media->with(['keywords' => function ($query) {
            $query->where('platform', PlatformEnum::NAVERBLOG)->where('state', 1);
        }])->get();

        foreach ($medias as $media) {
            foreach ($media->keywords as $keyword) {
                $keyword = $keyword->keyword;
                if (!$keyword) {
                    Log::error("not found available keywords");
                    return false;
                }

                $nodes = $this->naverBlogService->getNaverBlog($keyword);

                if (count($nodes) === 0) {
                    Log::error('no data!');
                    break;
                }
                foreach ($nodes['items'] as $node) {
                    try {
                        $article = $this->article->where([
                            'media_id' => $media->id,
                            'url' => $node['link']
                        ])->first();

                        if (!$article) {

                            $date = Carbon::parse($node->getCreatedTime())->format('Y-m-d H:i:s');
                            $id = Carbon::parse($date)->getTimestamp() * -1;
                            $has_media = false;

                            $this->article->create([
                                'id' => $id,
                                'media_id' => $media->id,
                                'article_owner_id' => $node['bloggername'],
                                'platform' => PlatformEnum::NAVERBLOG,
                                'url' => $node['link'],
                                'type' => ArticleType::KEYWORD,
                                'keyword' => $keyword,
                                'title' => str_replace('&quot;', '"', strip_tags($node['title'])),
                                'contents' => str_replace('&quot;', '"', strip_tags($node['description'])),
                                'storage_thumbnail_url' => '',
                                'thumbnail_url' => '',
                                'thumbnail_width' => 0,
                                'thumbnail_height' => 0,
                                'state' => 0,
                                'date' => Carbon::parse($node['postdate'])->format('Y-m-d H:i:s'),
                                'has_media' => false,
                            ]);
                        }

                        // 수집 정보 게시자 저장
                        $this->articleOwner->updateOrCreate(
                            [
                                'id' => $node['bloggername'],
                                'platform' => PlatformEnum::NAVERBLOG
                            ],
                            [
                                'name' => $node['bloggername']
                            ]
                        );

                        // 썸네일 어떤 방식으로 가져올지
                        // $dom = new \DOMDocument();

                        // @$dom->loadHTML(file_get_contents($node['link']));
                        // $frame = $dom->getElementById('mainFrame');
                    } catch (\Exception $e) {
                        Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                    }
                }
            }
        }
    }
}
