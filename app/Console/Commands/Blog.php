<?php

namespace App\Console\Commands;

use App\Board;
use App\Lib\Util;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Console\Command;

class Blog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:blog';

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
        $host = 'http://localhost:4444/wd/hub';
        $options = new ChromeOptions();
        $options->addArguments(['--headless', '--disable-dev-shm-usage', '--no-sandbox', '--lang=ko']);
        $caps = DesiredCapabilities::chrome();
        $caps->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = RemoteWebDriver::create($host, $caps);

        $artist_id = $this->artistsId;
        $names = \DB::table('artists')->where('id',$artist_id)->get(); // object
        try {
            foreach ($names as $name) {
                $country = \DB::table('countries')->where('name', $name->name)->get();
                $client_id = "H7okOrLzi4GT5xJCBjwl";
                $client_secret = "xKhY_toN10";
                $encText = urlencode($name->name . "여행");
                $url = "https://openapi.naver.com/v1/search/blog.json?query=".$encText."&display=10&sort=sim"; // json 결과

                $is_post = false;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, $is_post);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $headers = array();
                $headers[] = "X-Naver-Client-Id: ".$client_id;
                $headers[] = "X-Naver-Client-Secret: ".$client_secret;
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $response = curl_exec ($ch);
                $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                echo "status_code:".$status_code."";
                curl_close ($ch);

                if($status_code == 200) {
                    $array_data = json_decode($response, true);
                    $user = env('APP_NAME');

                    if ($user != null) {
                        $params['app'] = env('APP_NAME');
                    } else {
                        $params['app'] = 'fantatravel';
                    }

                    foreach ($array_data['items'] as $item) {

                        $dupleChk = $this->isValidation($item);
                        if ($dupleChk > 0) {
                            continue;
                        }
                        $search = 'naver';      //naver.com 링크로 된 블로그만 가져오기 위해, 지정하지 않으면 모든 블로그를 가져옴.
                        if(strpos($item['link'], $search)) {
                            $document = [
                                'artists_id' => $artist_id,
                                'app' => env('APP_NAME'),
                                'type' => $this->channelType,
                                'post' => $item['link'],
                                'post_type' => 'image',
//                                'sub_type' => 'tip',
//                                'sub_category' => 5,
                                'title' => str_replace('&quot;', '"', strip_tags($item['title'])),
                                'contents' => str_replace('&quot;', '"', strip_tags($item['description'])),
                                'recorded_at' => strftime("%Y-%m-%d %H:%M:%S", strtotime($item['postdate'])),
                                'state' => 0,
                                'cou_id' => $country[0]->id,
                            ];

                            $driver->get($item['link']);
                            $my_frame = $driver->findElement(WebDriverBy::id('mainFrame'));
                            $dd = $driver->switchTo()->frame($my_frame);
                            $metas = $dd->findElements(WebDriverBy::tagName('meta'));
                            $datas = $driver->findElements(WebDriverBy::className('se-image-resource'));

                            foreach ($metas as $meta) {
                                if($meta->getAttribute('property') == 'og:image') {
                                    $img_url = $meta->getAttribute('content');

                                    // file save
                                    if($img_url !== null) {
                                        $util = new Util();
                                        $resized_image = $util->AzureUploadImage($img_url, $this->channelImagePath);
                                        if($resized_image['fileName'] !== null) {
                                            $document['thumbnail_url'] = '/' . $this->channelImagePath . '/' . $resized_image['fileName'];
                                            $document['thumbnail_w'] = $resized_image['width'];
                                            $document['thumbnail_h'] = $resized_image['height'];
                                            $document['ori_thumbnail'] = $img_url;
//                                        $document['data'] = array(['image' => $document['thumbnail_url']]);
//                                        $document['ori_data'] = array($document['thumbnail_url']);
                                        }
                                    }
                                }
                            }
                            $data = [];
                            $oriData = [];
                            foreach ($datas as $d) {
                                $img_url = ($d->getAttribute('src'));
                                // file save
                                $util = new Util();
                                $resized_image = $util->AzureUploadImage($img_url, $this->channelImagePath);
                                if($resized_image['fileName'] !== null || $resized_image['fileName'] === '') {
                                    $data[] = [
                                        'image' => '/' . $this->channelImagePath . '/' . $resized_image['fileName']
                                    ];
                                    $oriData[] = $img_url;
                                }
                                $document['data'] = $data;
                                $document['ori_data'] = $oriData;
                            }
                            $board = Board::create($document);
                        }
                    } //for문
                }
            }
        } catch (StaleElementReferenceException $exception) {

        }
    }
}
