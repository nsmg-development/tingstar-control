<?php

namespace App\Console\Commands;

use App\Enums\ArticleType;
use App\Enums\PlatformEnum;
use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\Channel;
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

    protected string $maxId = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        InstagramService $instagramService,
        AzureService $azureService,
        Channel $channel,
        Article $article,
        ArticleMedia $articleMedia
    )
    {
        parent::__construct();

        $this->instagramService = $instagramService;
        $this->azureService = $azureService;
        $this->channel = $channel;
        $this->article = $article;
        $this->articleMedia = $articleMedia;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 인스타그램 로그인 계정 DB 참조
        $platformAccount = $this->instagramService->getPlatformAccount(PlatformEnum::INSTAGRAM, 0);
        if (!$platformAccount) {
            Log::error("not found available platform account");
            return false;
        }

        $login_id = $platformAccount->login_id;
        $login_password = $platformAccount->login_password;

        // 스크래핑 헤더 캐싱
        $headers = $this->instagramService->initInstagram($login_id, $login_password);

        // channel 정보 DB에서 가져오기
        $channels = $this->channel->active(PlatformEnum::INSTAGRAM)->get();

        // 채널 정보 가져오기 오류 발생
        if (count($channels) === 0) {
            Log::error("not found available channels");
            return false;
        }

        foreach($channels as $channel)
        {
            // 반복하여 스크래핑 처리
            do {
                $result = $this->instagramService->requestInstagramByAccount($headers, $channel->channel, 5, $this->maxId);

                if (count($result) === 0) {
                    Log::error('no data!');
                    break;
                }

                $this->maxId = $result['maxId'];
                $nodes = $result['medias'];

                foreach ($nodes as $node) {
                    // ArticleMediaType::getValueByName($node->getSidecarMedias()[0]->getType());
                    // try {
                        $article = $this->article->where([
                            'media_id' => 1,
                            'url' => $node->getLink()
                        ])->first();

                        $this->info('Check::' . $node->getLink());

                        if (!$article) {
                            $article = $this->article->create([
                                'media_id' => 1,
                                'platform' => PlatformEnum::INSTAGRAM,
                                'url' => $node->getLink(),
                                'type' => ArticleType::CHANNEL,
                                'channel' => $channel->channel,
                                'title' => '',
                                'contents' => $node->getCaption(),
                                'storage_thumbnail_url' => $this->azureService->AzureUploadImage($node->getImageThumbnail()['url'],  'images'),
                                'thumbnail_url' => $node->getImageThumbnail()['url'],
                                'thumbnail_width' => $node->getImageThumbnail()['width'],
                                'thumbnail_height' => $node->getImageThumbnail()['height'],
                                'hashtag' => $node->getHashTag(),
                                'state' => 0,
                                'date' => Carbon::parse($node->getCreatedTime())->format('Y-m-d H:i:s'),
                            ]);

                            $this->info('Created::', $node->getLink());

                            $articleMedias = $this->instagramService->getArticleMedias($article->id, $node->getType(), $node);

                            $this->articleMedia->insert($articleMedias);
                        }

                        sleep(1);
                    // } catch (\Exception $e) {
                    //     Log::error(sprintf('[%s:%d] %s', __FILE__, $e->getLine(), $e->getMessage()));
                    // }
                }

                // $this->info($this->maxId);
            } while ($this->maxId !== '');
        }
        return true;
    }
}
