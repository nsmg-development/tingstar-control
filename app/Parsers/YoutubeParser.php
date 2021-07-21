<?php

namespace App\Parsers;

class YoutubeParser
{

    protected string $title = '';
    protected string $description = '';
    protected string $thumbnailUrl = '';
    protected string $thumbnailWidth = '';
    protected string $thumbnailHeight = '';
    protected string $date = '';
    protected string $videoUrl = '';

    public function __construct($media)
    {
        $this->title = $media->snippet['title'];
        $this->description = $media->snippet['description'];

        $this->thumbnailUrl = (isset($media->snippet['thumbnails']['high'])) ? $media->snippet['thumbnails']['high']['url'] : '';
        $this->thumbnailWidth = (isset($media->snippet['thumbnails']['high'])) ? $media->snippet['thumbnails']['high']['width'] : '';
        $this->thumbnailHeight = (isset($media->snippet['thumbnails']['high'])) ? $media->snippet['thumbnails']['high']['height'] : '';

        $this->date = $media->snippet['publishedAt'];
        $this->videoUrl = "https://www.youtube.com/watch?v=" . $media->id['videoId'];
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->videoUrl;
    }

    /**
     * @return string
     */
    public function getThumbnailsUrl(): string
    {
        return $this->thumbnailUrl;
    }

    /**
     * @return int
     */
    public function getThumbnailWidth(): int
    {
        return $this->thumbnailWidth;
    }

    /**
     * @return int
     */
    public function getThumbnailHeight(): int
    {
        return $this->thumbnailHeight;
    }

    public function getCreatedTime()
    {
        return $this->date;
    }
}
