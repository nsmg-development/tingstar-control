<?php

namespace App\Parsers;

class InstagramParser
{
    protected int $created_at = 0;
    protected string $caption = '';
    protected int $ownerId = 0;
    protected string $ownerName = '';
    protected int $likesCount = 0;
    protected string $id = '';
    protected string $url = '';
    protected string $thumbnails = '';
    protected array $imageUrl;
    protected int $imageWidth = 0;
    protected int $imageHeight = 0;


    public function __construct($media)
    {

        $this->created_at = $media->taken_at;
        $this->caption = $media->caption['text'];
        $this->ownerId = $media->user['pk'];
        $this->ownerName = $media->user['username'];
        $this->likesCount = $media->like_count;
        $this->id = $media->id;
        $this->url = "https://www.instagram.com/p/$media->code";
        if($media->carousel_media) {
            $this->imageUrl = $media->carousel_media;
        }

        // $this->imageUrl = $file->image_versions2['candidates'][0]['url'];
        // $this->imageUrl = (isset($media->carousel_media)) ? $media->carousel_media[0]['image_versions2']['candidates'][0]['url'] : '';
        // $this->imageWidth = (isset($media->carousel_media)) ? $media->carousel_media[0]['image_versions2']['candidates'][0]['height'] : 0;
        // $this->imageHeight = (isset($media->carousel_media)) ? $media->carousel_media[0]['image_versions2']['candidates'][0]['height'] : 0;
        // $this->thumbnails = $media->thumbnails;
    }

    /**
     * @return int
     */
    public function getCreatedTime(): int
    {
        return $this->created_at;
    }

    /**
     * @return string
     */
    public function getCaption(): string
    {
        $caption = $this->caption;

        $hashTag = array();
        if (isset($caption) && !empty($caption)) {
            $_tag_ary = explode("#", $caption);
            if (count($_tag_ary) > 1) {
                for ($i = 0; $i < count($_tag_ary); $i++) {
                    $hashTag[] = trim($this->getPostCharacters($_tag_ary[$i]));
                }
            }
        }
        return $this->getPostCharacters($caption);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getHashTag(): string
    {
        $caption = $this->caption;
        if (isset($caption) && !empty($caption)) {
            preg_match_all('/#([\p{Pc}\p{N}\p{L}\p{Mn}]+)/u', $caption, $matches);
            return trim(implode(" ", $matches[0]));
        }
    }

    /**
     * @param $caption
     * @return string
     */
    public function getPostCharacters($caption): string
    {
        $caption = \Normalizer::normalize($caption, \Normalizer::FORM_C);
        $caption = preg_replace('/\r\n|\r|\n/', '', $caption);
        $caption = preg_replace('/[[:punct:]]/u', '', $caption);

        //이모지 제거
        $caption = preg_replace('/[^\p{L}\p{N}\p{P}\p{Z}]/u', '', $caption);

        return $caption;
    }

    /**
     * @return string
     */
    public function getThumbnailsUrl(): string
    {
        return $this->thumbnails;
    }

    /**
     * @return array
     */
    public function getImageUrl(): array
    {
        return $this->imageUrl;
    }

    /**
     * @return int
     */
    public function getImageWidth(): int
    {
        return $this->imageWidth;
    }

    /**
     * @return int
     */
    public function getImageHeight(): int
    {
        return $this->imageHeight;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function getOwnerName()
    {
        return $this->ownerName;
    }
}
