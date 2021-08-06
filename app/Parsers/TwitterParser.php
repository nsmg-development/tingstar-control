<?php

namespace App\Parsers;

class TwitterParser
{
    // protected string $thumbnailUrl = '';
    // protected string $thumbnailWidth = '';
    // protected string $thumbnailHeight = '';
    // protected string $videoUrl = '';
    protected string $mediaId = '';
    protected string $date = '';
    protected string $description = '';
    protected string $ownerId = '';
    protected string $ownerName = '';
    protected string $ownerImageUrl = '';
    protected string $ownerPageUrl = '';
    protected string $twitterUrl = 'https://twitter.com/';

    public function __construct($media, $user)
    {
        $this->mediaId = $media->id;
        $this->date = $media->created_at;
        $this->description = $media->text;
        $this->ownerId = $media->author_id;
        $this->ownerName = $user['username'];
        $this->ownerImageUrl = $user['profile_image_url'];
        $this->ownerPageUrl = $this->twitterUrl . $user['username'];
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
}
