<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;

class NaverBlogService
{
    protected string $url = "https://openapi.naver.com/v1/search/blog.json?";
    protected string $clientId = "_u0C7VkMSrTmUVhjTkB8";
    protected string $clientSecret = "LSdtehsW8d";
    protected int $display = 100;
    protected int $start = 1000;
    protected string $sort = "date";

    public function getNaverBlog(string $keyword): array
    {
        $response = Http::withHeaders([
            'X-Naver-Client-Id' => $this->clientId,
            'X-Naver-Client-Secret' => $this->clientSecret
        ])->get($this->url, [
            'query' => $keyword,
            'display' => $this->display,
            'sort' => $this->sort,
            'start' => $this->start,
        ]);
        return $response->json();
    }
}
