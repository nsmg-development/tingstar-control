<?php

namespace App\Parsers;

class TwitterParser
{
    protected string $thumbnailUrl = '';
    protected string $thumbnailWidth = '';
    protected string $thumbnailHeight = '';
    protected string $videoUrl = '';
    protected string $mediaType = '';
    protected string $mediaId = '';
    protected string $date = '';
    protected string $description = '';
    protected string $ownerId = '';
    protected string $ownerName = '';
    protected string $ownerImageUrl = '';
    protected string $ownerPageUrl = '';
    protected string $twitterUrl = 'https://twitter.com/';
    protected array $mediaKeys = [];
    protected array $medias = [];

    public function __construct($media, $user, $file)
    {


        $this->mediaId = $media->id;
        $this->date = $media->created_at;
        $this->description = $media->text;
        $this->ownerId = $media->author_id;
        $this->ownerName = $user->username;
        $this->ownerImageUrl = $user->profile_image_url;
        $this->ownerPageUrl = $this->twitterUrl . $user->username;
        $this->thumbnailUrl = $file->url ?? '';
        $this->mediaType = $file->type ?? '';
        $this->thumbnailWidth = $file->width ?? 0;
        $this->thumbnailHeight = $file->height ?? 0;
        $this->videoUrl = $file->url ?? '';
        if($media->attachments) {
            $this->mediaKeys = $media->attachments['media_keys'];
            $this->medias = (array)$file;
        }
    }

    /**
     * @return string
     */
    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getOwnerName(): string
    {
        return $this->ownerName;
    }

    public function getOwnerImageUrl(): string
    {
        return $this->ownerImageUrl;
    }

    public function getOwnerPageUrl(): string
    {
        return $this->ownerPageUrl;
    }

    public function getThumbnailUrl(): string
    {
        return $this->thumbnailUrl;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getThumbnailWidth(): string
    {
        return $this->thumbnailWidth;
    }

    public function getThumbnailHeight(): string
    {
        return $this->thumbnailHeight;
    }

    public function getVideoUrl(): string
    {
        return $this->videoUrl;
    }

    public function getMediaKeys(): array
    {
        return $this->mediaKeys;
    }

    public function getMedias(): array
    {
        return $this->medias;
    }
}
