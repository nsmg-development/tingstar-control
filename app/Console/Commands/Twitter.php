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
    protected string $ownerUrl = "https://twitter.com/";
    protected string $storageBaseUrl = "https://chuncheon.blob.core.windows.net/chuncheon/";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        PlatformEnum   $platformEnum,
        AzureService   $azureService,
        Article        $article,
        ArticleMedia   $articleMedia,
        Keyword        $keyword,
        Media          $media,
        ArticleOwner   $articleOwner,
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

                $lastRow = $this->article->where('media_id', $media->id)->where('platform', PlatformEnum::TWITTER)->orderBy('id')->first();

                $i = 0;
                do {
                    $result = $this->twitterService->getTwitter($keyword);

                    // 유튜브 데이터 없는 경우 오류 출력
                    if (count($result) === 0) {
                        Log::error('no data!');
                        break;
                    }

                    $this->nextPageToken = $result['medias']['meta']['next_token'];

                    $nodes = $result['medias']['data'];
                    $users = $result['medias']['includes']['users'];
                    $files = $result['medias']['includes']['media'];

                    foreach ($nodes as $node) {
                        foreach ($users as $user) {
                            try {
                                if ($lastRow) {
                                    if ($lastRow->media_id === $media->id && $lastRow->keyword === $keyword && $lastRow->url === $this->twitterUrl . $node['id']) {
                                        $this->info('stop!!!');
                                        break 2;
                                    }
                                }

                                $article = $this->article->where([
                                    'media_id' => $media->id,
                                    'url' => $this->twitterUrl . $node['id']
                                ])->first();

                                if (!$article) {

                                    $date = Carbon::parse($node['created_at'])->format('Y-m-d H:i:s');
                                    $id = Carbon::parse($date)->getTimestamp() * -1;
                                    $has_media = false;

                                    if (isset($node['attachments']['media_keys'])) {
                                        $has_media = true;
                                    }

                                    $article = $this->article->create([
                                        'id' => $id,
                                        'media_id' => $media->id,
                                        'article_owner_id' => $user['id'],
                                        'platform' => PlatformEnum::TWITTER,
                                        'url' => $this->twitterUrl . $node['id'],
                                        'type' => ArticleType::KEYWORD,
                                        'keyword' => $keyword,
                                        'title' => '',
                                        'contents' => $node['text'],
                                        'storage_thumbnail_url' => null,
                                        'thumbnail_url' => null,
                                        'thumbnail_width' => null,
                                        'thumbnail_height' => null,
                                        'state' => 0,
                                        'date' => $date,
                                        'has_media' => $has_media
                                    ]);

                                    // 수집 정보 게시자 저장

                                    if ($node['author_id'] === $user['id']) {

                                        if ($user['profile_image_url']) {
                                            $thumbnail = $this->azureService->AzureUploadImage($user['profile_image_url'], date('Y') . '/images');
                                            $size = getimagesize($this->storageBaseUrl . $thumbnail);
                                            $width = $size[0];
                                            $height = $size[1];
                                        }

                                        $this->articleOwner->updateOrCreate(
                                            [
                                                'id' => (string)$user['id'],
                                                'platform' => PlatformEnum::TWITTER
                                            ],
                                            [
                                                'name' => $user['username'],
                                                'url' => $this->ownerUrl . $user['username'],
                                                'storage_thumbnail_url' => $thumbnail ?? null,
                                                'thumbnail_url' => $user['profile_image_url'] ?? null,
                                                'thumbnail_width' => $width ?? null,
                                                'thumbnail_height' => $height ?? null,
                                            ]
                                        );
                                    }

                                    if (isset($node['attachments']['media_keys'])) {
                                        foreach ($node['attachments']['media_keys'] as $media_key) {
                                            foreach ($files as $media) {
                                                if ($media_key === $media['media_key']) {
                                                    if ($media['type'] === 'photo') {
                                                        $thumbnail = $this->azureService->AzureUploadImage($media['url'], date('Y') . '/images');
                                                        $size = getimagesize($this->storageBaseUrl . $thumbnail);
                                                        $width = $size[0];
                                                        $height = $size[1];
                                                        $mime = $size['mime'];

                                                        $this->articleMedia->create([
                                                            'article_id' => $id,
                                                            'type' => ArticleMediaType::IMAGE,
                                                            'storage_url' => $thumbnail,
                                                            'url' => $media['url'],
                                                            'width' => $width,
                                                            'height' => $height,
                                                            'mime' => $mime
                                                        ]);
                                                    }

                                                    if ($media['type'] === 'video') {
                                                        $this->articleMedia->create([
                                                            'article_id' => $id,
                                                            'type' => ArticleMediaType::VIDEO,
                                                            'url' => $media['url'],
                                                            'width' => $media['width'],
                                                            'height' => $media['height'],
                                                        ]);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                $i++;
                                $this->info($i . ':' . $this->twitterUrl . $node['id']);
                                sleep(5);
                            } catch (\Exception $e) {
                                Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                            }
                        }
                    }
                    if ($this->nextPageToken == '') {
                        break 2;
                    }
                    $this->info($keyword);
                    $this->info($this->nextPageToken);
                } while ($i < 100);
            }
        }
        return true;
    }
}
