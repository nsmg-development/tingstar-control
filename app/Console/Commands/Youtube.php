<?php

namespace App\Console\Commands;

use App\Board;
use App\Lib\Util;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Ixudra\Curl\Facades\Curl;

class Youtube extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:youtube';

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
        $names = \DB::table('artists')->where('id',$artist_id)->get(); // object

        foreach ($names as $name) {
            $country = \DB::table('countries')->where('name', $name->name)->get();
            $key = 'AIzaSyAGHxBptJAXvHYWX2BF9FPKpTYvqBsDRQo';
//            $key = 'AIzaSyAdBFaHvuTLDILaI8frV1LwQxmCL0Cs6wQ';
            $option = array(
                'key' => $key,
                'part' => 'snippet',
                'q' => $name->name . "여행",
                'type' => 'video',
                'maxResults' => $this->max_results,
                'order' => 'date',
            );
            $call_api = $this->search_url.http_build_query($option, 'a', '&');
            $response = Curl::to($call_api)->get();
            $response = json_decode($response, true);
            foreach ($response['items'] as $item) {
                $dupleChk = $this->isValidation($item['id']['videoId']);
                if ($dupleChk > 0) {
                    continue;
                }
                $document = [
                    'artists_id' => $artist_id,
                    'app' => env('APP_NAME'),
                    'type' => $this->channelType,
                    'post' => $item['id']['videoId'],
                    'title' => $item['snippet']['title'],
                    'contents' => str_replace('&quot;', '"', strip_tags($item['snippet']['description'])),
                    'sns_account' => $item['snippet']['channelId'],
                    'ori_tag' => [],
                    'recorded_at' => Carbon::parse($item['snippet']['publishedAt'])->format('Y-m-d H:i:s'),
                    'state' => 0,
                    'cou_id' => $country[0]->id,
                ];

                $thumbnail = $item['snippet']['thumbnails']['high'];

                if ($thumbnail !== null) {
                    $util = new Util();
                    $response = $util->AzureUploadImageCropped($thumbnail['url'], $this->channelImagePath, $item['id']['videoId'] . '_');
                    $document['thumbnail_url'] = '/' . $this->channelImagePath . '/' . $response['fileName'];
                    $document['thumbnail_w'] = $response['width'];
                    $document['thumbnail_h'] = $response['height'];
                    $document['ori_thumbnail'] = $thumbnail['url'];

                    $document['data'] = array(['image' => $document['thumbnail_url']]);
                    $document['ori_data'] = array($document['thumbnail_url']);
                }
            }
            if(isset($document)) {
                $board = Board::create($document);
            }
        }
    }
}
