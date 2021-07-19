<?php

namespace App\Console\Commands;

use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class NaverBlog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:naver-blog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected Article $article;
    protected String $keyword = "에버랜드";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Article $article)
    {
        parent::__construct();

        $this->article = $article;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $response = Http::withHeaders([
            'X-Naver-Client-Id' => '_u0C7VkMSrTmUVhjTkB8',
            'X-Naver-Client-Secret' => 'LSdtehsW8d'
        ])->get('https://openapi.naver.com/v1/search/blog.json?', [
            'query' => '에버랜드',
            'display' => 10
        ]);

        $nodes = $response->json();
        foreach ($nodes['items'] as $node) {
            $this->article->create([
                'media_id' => 1,
                'platform' => 'BLOG',
                'keyword' => $this->keyword,
                'url' => $node['link'],
                'title' => $node['title'],
                'contents' => $node['description'],
                'thumbnail_url' => '',
                'state' => 0,
                'date' => Carbon::parse($node['postdate'])->format('Y-m-d'),
            ]);

            $html = file_get_contents($node['link']);
            dd($html->find('a'));

//            $metas = $doc->getElementsByTagName('meta');
//            dd($metas);
        }

//
//        $doc = new \DOMDocument();
//        @$doc->loadHTML($html);
//
//        $metas = $doc->getElementsByTagName('meta');
//        $img_url = "";
//        for ($i = 0; $i < $metas->length; $i++)
//        {
//            $meta = $metas->item($i);
//            if($meta->getAttribute('property') == 'og:image') {
//                $img_url = $meta->getAttribute('content');
//            }
//        }
    }
}
