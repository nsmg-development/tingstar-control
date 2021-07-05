<?php

namespace App\Console\Commands;

use App\Board;
use App\Lib\Util;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InstagramScraper\Model\Media;

class Instagram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:instagram';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $artist_id = $this->artistsId;
        $names = \DB::table('artists')->where('id',$artist_id)->get();

        foreach ($names as $name) {
            $country = \DB::table('countries')->where('name', $name->name)->get();
            $instagram = new \InstagramScraper\Instagram();
            $instagramPageObj = $instagram->getPaginateMediasByTag($name->name."여행");
            $instagramPageObj['hasNextPage'] = true;

            $cnt =0;

            while ($instagramPageObj['hasNextPage'] === true) {
                if(!empty($instagramPageObj['medias'])) {
                    foreach ($instagramPageObj['medias'] as $key => $media) {
                        Log::info(__METHOD__.' - media - '.json_encode($media));
                        $cnt++;
                        $duplicationCheck = $this->isValidation( $media);
                        if (!empty($duplicationCheck) || $cnt > 30) {
                            $instagramPageObj['hasNextPage'] = false;
                            break 2;
                        }
                        $media = $instagram->getMediaById($media->getId());
                        if(!$media) continue;
                        $detailMedias = $media->getSidecarMedias();

                        $board = $this->setDataFormatting($media);
                        $board['artists_id'] = $this->artistsId;
                        $board['cou_id'] = $country[0]->id;
                        parent::saveData($board);
                    }
                }
                $instagramPageObj = $instagram->getPaginateMedias($this->channelKey, $instagramPageObj['maxId']);
            }
        }
        Log::info(__METHOD__ . " - Success Process Cnt : " . parent::$successCnt);
        return true;
    }

    public function isValidation( $channelModal): bool
    {
        $chk = Board::where('post', '=', $this->parsingPost($channelModal))->count();
        return $chk;
    }


    public function parsingPost( $channelModa): string
    {
        return '/p/' . $channelModa->getShortCode() . '/';
    }


    public function setDataFormatting( $channelModal): Board
    {
        $util = new Util();

        $board = new Board();
        $board->app = env('APP_NAME');
        $board->type = $this->channelType;
        $board->post = $this->parsingPost($channelModal);
        $board->post_type = $channelModal->getType() ;
        $board->title = '';
        $board->contents = $channelModal->getCaption();
        $board->sns_account = $this->channelKey;
        $board->gender = 1;
        $board->state = 0;
        $board->recorded_at = date('Y-m-d H:i:s', $channelModal->getCreatedTime());
        $board->created_at = date('Y-m-d H:i:s');

        $data = [];
        $oriData = [];

        $file = file_get_contents($channelModal->getImageLowResolutionUrl());
        $thumbnail = $util->AzureUploadImage($channelModal->getImageLowResolutionUrl(), $this->channelImagePath);
        $board->thumbnail_url = "/" . $this->channelImagePath . $thumbnail['fileName'];
        $board->thumbnail_w = (int)$thumbnail['width'];
        $board->thumbnail_h = (int)$thumbnail['height'];
        $board->ori_thumbnail = $channelModal->getImageLowResolutionUrl();

        //media 값이 비어서 올때가 있다
        if($board->post_type == 'image'){
            $data[0]['image'] = $board->thumbnail_url;
        }else{
            $data[0]['video']['poster'] = $board->thumbnail_url;
        }

        $detailMedias = $channelModal->getSidecarMedias();

        foreach ($detailMedias as $detailMediaKey => $detailMedia) {
            $oriData[] = $detailMedia->getImageLowResolutionUrl();
            if ($detailMedia->getType() === Media::TYPE_IMAGE) {
                $thumbnail = $util->AzureUploadImage($detailMedia->getImageLowResolutionUrl(), $this->channelImagePath);
                $data[$detailMediaKey]['image'] = "/" . $this->channelImagePath . $thumbnail['fileName'];
            } else {
                if ($detailMedia->getType() === Media::TYPE_VIDEO) {

                    if ($detailMediaKey == 0) {
                        unset($data[0]['image']);
                    }

                    $thumbnail = $util->AzureUploadImage($detailMedia->getImageLowResolutionUrl(),
                        $this->channelViedeoPath);
                    $data[$detailMediaKey]['video']['poster'] = "/" . $this->channelImagePath . $thumbnail['fileName'];

                    //$thumbnail = $util->AzureUploadImage($detailMedia->getVideoStandardResolutionUrl(), $this->channelViedeoPath);
                    //$data[$detailMediaKey]['video']['src'] = "/" .$this->channelViedeoPath. $thumbnail['fileName'];
                }
            }
        }

        $board->data = $data;
        $board->ori_data = $oriData;

        return $board;
    }
}
