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
use App\Services\InstagramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Instagram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:instagram:keyword';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '인스타그램 크롤링(키워드)';

    protected InstagramService $instagramService;
    protected AzureService $azureService;
    protected Article $article;
    protected ArticleMedia $articleMedia;
    protected Keyword $keyword;
    protected Media $media;
    protected ArticleOwner $articleOwner;
    protected string $maxId = '';
    protected string $storageBaseUrl = "https://chuncheon.blob.core.windows.net/chuncheon/";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        InstagramService $instagramService,
        AzureService     $azureService,
        Article          $article,
        ArticleMedia     $articleMedia,
        Keyword          $keyword,
        Media            $media,
        ArticleOwner     $articleOwner
    )
    {
        parent::__construct();

        $this->instagramService = $instagramService;
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
        // $resized_image = $this->azureService->AzureUploadImage("https://i.ytimg.com/vi/yU3qEoRSUgI/hqdefault.jpg", "image", "300", "400");
        // 인스타그램 로그인 계정 DB 참조
        $platformAccount = $this->instagramService->getPlatformAccount(PlatformEnum::INSTAGRAM, 1000);
        if (!$platformAccount) {
            Log::error("not found available platform account");
            return false;
        }
        $login_id = $platformAccount->login_id;
        $login_password = $platformAccount->login_password;

        $medias = $this->media->with(['keywords' => function ($query) {
            $query->where('platform', PlatformEnum::INSTAGRAM)->where('state', 1);
        }])->get();

        // 스크래핑 헤더 캐싱
        $headers = $this->instagramService->initInstagram($login_id, $login_password);

        // 로그인 헤더 생성 오류 발생
        if (count($headers) === 0) {
            Log::error("Init instagram headers fail");
            return false;
        }

        foreach ($medias as $media) {
            foreach ($media->keywords as $keyword) {
                $keyword = $keyword->keyword;

                // 키워드 정보 가져오기 오류 발생
                if (!$keyword) {
                    Log::error("not found available keywords");
                    return false;
                }

                $lastRow = $this->article->where('media_id', $media->id)->where('platform', PlatformEnum::INSTAGRAM)->orderBy('id')->first();

                // 최근 게시물로(recent nodes) 부터 반복하여 스크래핑 처리
                $i = 0;
                do {
                    $scraped = $this->instagramService->requestInstagramByKeyword($headers, $keyword, $this->maxId);

                    $result = $this->instagramService->getInstagramByKeyword($keyword, $scraped);

                    if (count($result) === 0) {
                        Log::error('no data!');
                        break;
                    }

                    $this->maxId = $result['maxId'];
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

                                if ($node->getImageUrl()) {
                                    $has_media = true;
                                }

                                $article = $this->article->create([
                                    'id' => $id,
                                    'media_id' => $media->id,
                                    'article_owner_id' => $node->getOwnerId(),
                                    'platform' => PlatformEnum::INSTAGRAM,
                                    'url' => $node->getUrl(),
                                    'type' => ArticleType::KEYWORD,
                                    'keyword' => $keyword,
                                    'title' => '',
                                    'contents' => $node->getCaption(),
                                    'thumbnail_url' => null,
                                    'storage_thumbnail_url' => null,
                                    'hashtag' => $node->getHashTag(),
                                    'state' => 0,
                                    'date' => $date,
                                    'has_media' => $has_media
                                ]);

                                // 수집 정보 게시자 저장
                                $this->articleOwner->updateOrCreate(
                                    [
                                        'id' => $node->getOwnerId(),
                                        'platform' => PlatformEnum::INSTAGRAM
                                    ],
                                    [
                                        'name' => $node->getOwnerName(),
                                    ]
                                );

                                if ($node->getImageUrl()) {

                                    $thumbnail = $this->azureService->AzureUploadImage($node->getImageUrl(), date('Y') . '/images');
                                    $size = getimagesize($this->storageBaseUrl . $thumbnail);
                                    $width = $size[0];
                                    $height = $size[1];
                                    $mime = $size['mime'];

                                    $this->articleMedia->create([
                                        'article_id' => $id,
                                        'type' => ArticleMediaType::IMAGE,
                                        'storage_url' => $thumbnail,
                                        'url' => $node->getImageUrl() ?? null,
                                        'width' => $width,
                                        'height' => $height,
                                        'mime' => $mime
                                    ]);
                                }
                            }
                            $i++;
                            sleep(1);
                        } catch (\Exception $e) {
                            Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                        }
                    } //node for
                    if ($this->maxId == '') {
                        break 2;
                    }
                    $this->info($i . ':' . $node->getUrl());
                    $this->info($keyword);
                    $this->info($this->maxId);
                } while ($i < 10000);
            }
        }
        // 계정 사용횟수 업데이트
        $platformAccount->increment('use_count');

        return true;
    }
}
