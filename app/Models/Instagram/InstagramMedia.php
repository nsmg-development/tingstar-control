<?php

namespace App\Models\Instagram;

/**
 * Class Media
 */
class InstagramMedia extends AbstractModel
{
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_SIDECAR = 'sidecar';
    const TYPE_CAROUSEL = 'carousel';

    /**
     * @var string
     */
    protected $id = '';

    /**
     * @var string
     */
    protected $shortCode = '';

    /**
     * @var int
     */
    protected $createdTime = 0;

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var string
     */
    protected $link = '';

    /**
     * @var string
     */
    protected $imageLowResolutionUrl = '';

    /**
     * @var int
     */
    protected $imageLowResolutionWidth = 0;

    /**
     * @var int
     */
    protected $imageLowResolutionHeight = 0;

    /**
     * @var string
     */
    protected $imageThumbnailUrl = '';

    /**
     * @var int
     */
    protected $imageThumbnailWidth = 0;

    /**
     * @var int
     */
    protected $imageThumbnailHeight = 0;

    /**
     * @var string
     */
    protected $imageStandardResolutionUrl = '';

    /**
     * @var int
     */
    protected $imageStandardResolutionWidth = 0;

    /**
     * @var int
     */
    protected $imageStandardResolutionHeight = 0;

    /**
     * @var string
     */
    protected $imageHighResolutionUrl = '';

    /**
     * @var int
     */
    protected $imageHighResolutionWidth = 0;

    /**
     * @var int
     */
    protected $imageHighResolutionHeight = 0;

    /**
     * @var array
     */
    protected $squareImages = [];

    /**
     * @var array
     */
    protected $carouselMedia = [];

    /**
     * @var string
     */
    protected $caption = '';

    /**
     * @var string
     */
    protected $hashTag = '';

    /**
     * @var bool
     */
    protected $isCaptionEdited = false;

    /**
     * @var bool
     */
    protected $isAd = false;

    /**
     * @var string
     */
    protected $videoLowResolutionUrl = '';

    /**
     * @var string
     */
    protected $videoStandardResolutionUrl = '';

    /**
     * @var integer
     */
    protected $videoDuration = '';

    /**
     * @var string
     */
    protected $videoLowBandwidthUrl = '';

    /**
     * @var int
     */
    protected $videoViews = 0;

    /**
     * @var InstagramAccount
     */
    protected $owner;

    /**
     * @var int
     */
    protected $ownerId = 0;

    /**
     * @var int
     */
    protected $likesCount = 0;

    /**
     * @var boolean
     */
    protected $hasLiked = null;

    /**
     * @var
     */
    protected $locationId;

    /**
     * @var string
     */
    protected $locationName = '';

    /**
     * @var bool
     */
    protected $commentsDisabled = false;

    /**
     * @var string
     */
    protected $commentsCount = 0;

    /**
     * @var InstagramComment|array
     */
    protected $comments = [];

    /**
     * @var InstagramComment|array
     */
    protected $previewComments = [];

    /**
     * @var bool
     */
    protected $hasMoreComments = false;

    /**
     * @var string
     */
    protected $commentsNextPage = '';

    /**
     * @var InstagramMedia[]|array
     */
    protected $sidecarMedias = [];

    /**
     * @var string
     */
    protected $locationSlug;

    /**
     * @var string
     */
    protected $altText;

    /**
     * @var string
     */
    protected $locationAddressJson;

    /**
     * @param string $code
     *
     * @return int
     */
    public static function getIdFromCode(string $code)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $id = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $c = $code[$i];
            $id = $id * 64 + strpos($alphabet, $c);
        }
        return $id;
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public static function getLinkFromId(string $id)
    {
        $code = InstagramMedia::getCodeFromId($id);
        return Endpoint::getMediaPageLink($code);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public static function getCodeFromId(string $id): string
    {
        $parts = explode('_', $id);
        $id = $parts[0];
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
        $code = '';
        while ($id > 0) {
            $remainder = $id % 64;
            $id = ($id - $remainder) / 64;
            $code = $alphabet[$remainder] . $code;
        };
        return $code;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    /**
     * @return int
     */
    public function getCreatedTime(): int
    {
        return $this->createdTime;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function getImageLowResolutionUrl(): string
    {
        return $this->imageLowResolutionUrl;
    }


    /**
     * @return string
     */
    public function getImageThumbnailUrl(): string
    {
        return $this->imageThumbnailUrl;
    }

    /**
     * @return string
     */
    public function getImageStandardResolutionUrl(): string
    {
        return $this->imageStandardResolutionUrl;
    }

    /**
     * @return string
     */
    public function getImageHighResolutionUrl(): string
    {
        return $this->imageHighResolutionUrl;
    }


    /**
     * @return array
     */
    public function getSquareImages(): array
    {
        return $this->squareImages;
    }


    /**
     * @return array
     */
    public function getCarouselMedia(): array
    {
        return $this->carouselMedia;
    }

    /**
     * @return string
     */
    public function getCaption(): string
    {
        return $this->caption;
    }

    /**
     * @return bool
     */
    public function isCaptionEdited(): bool
    {
        return $this->isCaptionEdited;
    }

    /**
     * @return bool
     */
    public function isAd(): bool
    {
        return $this->isAd;
    }

    /**
     * @return string
     */
    public function getVideoLowResolutionUrl(): string
    {
        return $this->videoLowResolutionUrl;
    }

    /**
     * @return string
     */
    public function getVideoStandardResolutionUrl(): string
    {
        return $this->videoStandardResolutionUrl;
    }

    /**
     * @return integer
     */
    public function getVideoDuration()
    {
        return $this->videoDuration;
    }

    /**
     * @return string
     */
    public function getVideoLowBandwidthUrl(): string
    {
        return $this->videoLowBandwidthUrl;
    }

    /**
     * @return int
     */
    public function getVideoViews(): int
    {
        return $this->videoViews;
    }

    /**
     * @return int
     */
    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    /**
     * @return int
     */
    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    /**
     * @return boolean
     */
    public function getHasLiked(): ?bool
    {
        return $this->hasLiked;
    }

    /**
     * @return mixed
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @return string
     */
    public function getLocationName(): string
    {
        return $this->locationName;
    }

    /**
     * @return bool
     */
    public function getCommentsDisabled(): bool
    {
        return $this->commentsDisabled;
    }

    /**
     * @return string
     */
    public function getCommentsCount()
    {
        return $this->commentsCount;
    }

    /**
     * @return array
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @return array
     */
    public function getPreviewComments()
    {
        return $this->previewComments;
    }

    /**
     * @return bool
     */
    public function hasMoreComments(): bool
    {
        return $this->hasMoreComments;
    }

    /**
     * @return string
     */
    public function getCommentsNextPage(): string
    {
        return $this->commentsNextPage;
    }

    /**
     * @return InstagramMedia[]|array
     */
    public function getSidecarMedias(): array
    {
        return $this->sidecarMedias;
    }

    /**
     * @return string
     */
    public function getLocationSlug(): string
    {
        return $this->locationSlug;
    }

    /**
     * @return string
     */
    public function getAltText(): string
    {
        return $this->altText;
    }

    /**
     * @return string
     */
    public function getLocationAddressJson(): string
    {
        return $this->locationAddressJson;
    }

    /**
     * @return mixed
     */
    public function getLocationAddress()
    {
        return json_decode($this->locationAddressJson);
    }

    /**
     * @param string
     */
    public function setLocationName($name)
    {
        $this->locationName = $name;
    }

    /**
     * @param string
     */
    public function setLocationSlug($slug)
    {
        $this->locationSlug = $slug;
    }

    /**
     * @param $value
     * @param $prop
     * @param $arr
     */
    protected function initPropertiesCustom($value, $prop, $arr)
    {
        switch ($prop) {
            case 'id':
                $this->id = $value;
                break;
            case 'type':
                $this->type = $value;
                break;
            case 'date':
            case 'created_time':
                $this->createdTime = (int)$value;
                break;
            case 'shortcode':
            case 'code':
                $this->shortCode = $value;
                $this->link = Endpoint::getMediaPageLink($this->shortCode);
                break;
            case 'link':
                $this->link = $value;
                break;
            case 'comments_disabled':
                $this->commentsDisabled = $value;
                break;
            case 'comments':
                $this->commentsCount = $arr[$prop]['count'];
                break;
            case 'edge_media_preview_like':
            case 'edge_liked_by':
            case 'likes':
                $this->likesCount = $arr[$prop]['count'];
                break;
            case 'display_resources':
                foreach ($value as $media) {
                    $mediasUrl[] = $media['src'];
                    switch ($media['config_width']) {
                        case 640:
                            $this->imageThumbnailUrl = $media['src'];
                            $this->imageThumbnailWidth = $media['config_width'];
                            $this->imageThumbnailHeight = $media['config_height'];
                            break;
                        case 750:
                            $this->imageLowResolutionUrl = $media['src'];
                            $this->imageLowResolutionWidth = $media['config_width'];
                            $this->imageLowResolutionHeight = $media['config_height'];
                            break;
                        case 1080:
                            $this->imageStandardResolutionUrl = $media['src'];
                            $this->imageStandardResolutionWidth = $media['config_width'];
                            $this->imageStandardResolutionHeight = $media['config_height'];
                            break;
                    }
                }
                break;
            case 'thumbnail_resources':
                $squareImagesUrl = [];
                foreach ($value as $squareImage) {
                    $squareImagesUrl[] = $squareImage['src'];
                }
                $this->squareImages = $squareImagesUrl;
                break;
            case 'display_url':
                $this->imageHighResolutionUrl = $value;
                break;
            case 'display_src':
                $this->imageHighResolutionUrl = $value;
                if (!isset($this->type)) {
                    $this->type = static::TYPE_IMAGE;
                }
                break;
            case 'thumbnail_src':
                $this->imageThumbnailUrl = $value;
                break;
            case 'carousel_media':
                $this->type = self::TYPE_CAROUSEL;
                $this->carouselMedia = [];
                foreach ($arr["carousel_media"] as $carouselArray) {
                    self::setCarouselMedia($arr, $carouselArray, $this);
                }
                break;
            case 'caption':
                $this->caption = $arr[$prop];
                break;
            case 'accessibility_caption':
                $this->altText = $value;
                break;
            case 'video_views':
                $this->videoViews = $value;
                $this->type = static::TYPE_VIDEO;
                break;
            case 'videos':
                $this->videoLowResolutionUrl = $arr[$prop]['low_resolution']['url'];
                $this->videoStandardResolutionUrl = $arr[$prop]['standard_resolution']['url'];
                $this->videoLowBandwidthUrl = $arr[$prop]['low_bandwidth']['url'];
                break;
            case 'video_duration':
                $this->videoDuration = $arr[$prop];
                break;
            case 'video_resources':
                foreach ($value as $video) {
                    if ($video['profile'] == 'MAIN') {
                        $this->videoStandardResolutionUrl = $video['src'];
                    } elseif ($video['profile'] == 'BASELINE') {
                        $this->videoLowResolutionUrl = $video['src'];
                        $this->videoLowBandwidthUrl = $video['src'];
                    }
                }
                break;
            case 'location':
                if (isset($arr[$prop])) {
                    $this->locationId = $arr[$prop]['id'] ?: null;
                    $this->locationName = $arr[$prop]['name'] ?: null;
                    $this->locationSlug = $arr[$prop]['slug'] ?: null;
                    $this->locationAddressJson = $arr[$prop]['address_json'] ?? null;
                }
                break;
            case 'owner':
            case 'user':
                $this->owner = InstagramAccount::create($arr[$prop]);
                break;
            case 'is_video':
                if ($value) {
                    $this->type = static::TYPE_VIDEO;
                }
                break;
            case 'video_url':
                $this->videoStandardResolutionUrl = $value;
                break;
            case 'video_view_count':
                $this->videoViews = $value;
                break;
            case 'caption_is_edited':
                $this->isCaptionEdited = $value;
                break;
            case 'is_ad':
                $this->isAd = $value;
                break;
            case 'taken_at_timestamp':
                $this->createdTime = $value;
                break;
            case 'edge_media_preview_comment':
                if (isset($arr[$prop]['count'])) {
                    $this->commentsCount = (int)$arr[$prop]['count'];
                }
                if (isset($arr[$prop]['edges']) && is_array($arr[$prop]['edges'])) {
                    foreach ($arr[$prop]['edges'] as $commentData) {
                        $this->previewComments[] = InstagramComment::create($commentData['node']);
                    }
                }
                break;
            case 'edge_media_to_comment':
            case 'edge_media_to_parent_comment':
                if (isset($arr[$prop]['count'])) {
                    $this->commentsCount = (int)$arr[$prop]['count'];
                }
                if (isset($arr[$prop]['edges']) && is_array($arr[$prop]['edges'])) {
                    foreach ($arr[$prop]['edges'] as $commentData) {
                        $this->comments[] = InstagramComment::create($commentData['node']);
                    }
                }
                if (isset($arr[$prop]['page_info']['has_next_page'])) {
                    $this->hasMoreComments = (bool)$arr[$prop]['page_info']['has_next_page'];
                }
                if (isset($arr[$prop]['page_info']['end_cursor'])) {
                    $this->commentsNextPage = (string)$arr[$prop]['page_info']['end_cursor'];
                }
                break;
            case 'viewer_has_liked':
                $this->hasLiked = $arr[$prop];
                break;
            case 'edge_media_to_caption':
                if (is_array($arr[$prop]['edges']) && !empty($arr[$prop]['edges'])) {
                    $first_caption = $arr[$prop]['edges'][0];
                    if (is_array($first_caption) && isset($first_caption['node'])) {
                        if (is_array($first_caption['node']) && isset($first_caption['node']['text'])) {
                            $this->caption = $arr[$prop]['edges'][0]['node']['text'];
                        }
                    }
                }
                break;
            case 'edge_sidecar_to_children':
                if (!is_array($arr[$prop]['edges'])) {
                    break;
                }
                foreach ($arr[$prop]['edges'] as $edge) {
                    if (!isset($edge['node'])) {
                        continue;
                    }

                    $this->sidecarMedias[] = static::create($edge['node']);
                }
                break;
            case '__typename':
                if ($value == 'GraphImage' || $value == 'GraphStoryImage') {
                    $this->type = static::TYPE_IMAGE;
                } else if ($value == 'GraphVideo' || $value == 'GraphStoryVideo') {
                    $this->type = static::TYPE_VIDEO;
                } else if ($value == 'GraphSidecar') {
                    $this->type = static::TYPE_SIDECAR;
                }
                break;
        }
        if (!$this->ownerId && !is_null($this->owner)) {
            $this->ownerId = $this->getOwner()->getId();
        }
    }

    /**
     * @param $mediaArray
     * @param $carouselArray
     * @param $instance
     *
     * @return mixed
     */
    private static function setCarouselMedia($mediaArray, $carouselArray, $instance)
    {
        $carouselMedia = new InstagramCarouselMedia();
        $carouselMedia->setType($carouselArray['type']);

        if (isset($carouselArray['images'])) {
            $carouselImages = self::getImageUrls($carouselArray['images']['standard_resolution']['url']);
            $carouselMedia->setImageLowResolutionUrl($carouselImages['low']);
            $carouselMedia->setImageThumbnailUrl($carouselImages['thumbnail']);
            $carouselMedia->setImageStandardResolutionUrl($carouselImages['standard']);
            $carouselMedia->setImageHighResolutionUrl($carouselImages['high']);
        }

        if ($carouselMedia->getType() === self::TYPE_VIDEO) {
            if (isset($mediaArray['video_views'])) {
                $carouselMedia->setVideoViews($carouselArray['video_views']);
            }
            if (isset($carouselArray['videos'])) {
                $carouselMedia->setVideoLowResolutionUrl($carouselArray['videos']['low_resolution']['url']);
                $carouselMedia->setVideoStandardResolutionUrl($carouselArray['videos']['standard_resolution']['url']);
                $carouselMedia->setVideoLowBandwidthUrl($carouselArray['videos']['low_bandwidth']['url']);
            }
        }
        array_push($instance->carouselMedia, $carouselMedia);
        return $mediaArray;
    }

    /**
     * @param string $imageUrl
     *
     * @return array
     */
    private static function getImageUrls(string $imageUrl): array
    {
        $parts = explode('/', parse_url($imageUrl)['path']);
        $imageName = $parts[sizeof($parts) - 1];

        return [
            'thumbnail' => Endpoint::INSTAGRAM_CDN_URL . 't/s150x150/' . $imageName,
            'low' => Endpoint::INSTAGRAM_CDN_URL . 't/s320x320/' . $imageName,
            'standard' => Endpoint::INSTAGRAM_CDN_URL . 't/s640x640/' . $imageName,
            'high' => Endpoint::INSTAGRAM_CDN_URL . 't/' . $imageName,
        ];
    }


    /**
     * @return InstagramAccount
     */
    public function getOwner(): InstagramAccount
    {
        return $this->owner;
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
     * @return array
     */
    public function getImageThumbnail(): array
    {
        return [
            'url' => $this->imageThumbnailUrl,
            'width' => $this->imageThumbnailWidth,
            'height' => $this->imageThumbnailHeight
        ];
    }

    /**
     * @return array
     */
    public function getImageLowResolution(): array
    {
        return [
            'url' => $this->imageLowResolutionUrl,
            'width' => $this->imageLowResolutionWidth,
            'height' => $this->imageLowResolutionHeight
        ];
    }

    /**
     * @return array
     */
    public function getImageStandardResolution(): array
    {
        return [
            'url' => $this->imageStandardResolutionUrl,
            'width' => $this->imageStandardResolutionWidth,
            'height' => $this->imageStandardResolutionHeight
        ];
    }
}
