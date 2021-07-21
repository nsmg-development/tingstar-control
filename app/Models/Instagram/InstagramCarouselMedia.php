<?php

namespace App\Models\Instagram;

/**
 * Class CarouselMedia
 */
class InstagramCarouselMedia
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $imageLowResolutionUrl;

    /**
     * @var string
     */
    private $imageThumbnailUrl;

    /**
     * @var string
     */
    private $imageStandardResolutionUrl;

    /**
     * @var string
     */
    private $imageHighResolutionUrl;

    /**
     * @var string
     */
    private $videoLowResolutionUrl;

    /**
     * @var string
     */
    private $videoStandardResolutionUrl;

    /**
     * @var string
     */
    private $videoLowBandwidthUrl;

    /**
     * @var
     */
    private $videoViews;

    /**
     * CarouselMedia constructor.
     */
    public function __construct()
    {
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return $this
     */
    public function setType($type): InstagramCarouselMedia
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getImageLowResolutionUrl(): string
    {
        return $this->imageLowResolutionUrl;
    }

    /**
     * @param mixed $imageLowResolutionUrl
     *
     * @return InstagramCarouselMedia
     */
    public function setImageLowResolutionUrl($imageLowResolutionUrl): InstagramCarouselMedia
    {
        $this->imageLowResolutionUrl = $imageLowResolutionUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getImageThumbnailUrl(): string
    {
        return $this->imageThumbnailUrl;
    }

    /**
     * @param mixed $imageThumbnailUrl
     *
     * @return InstagramCarouselMedia
     */
    public function setImageThumbnailUrl($imageThumbnailUrl): InstagramCarouselMedia
    {
        $this->imageThumbnailUrl = $imageThumbnailUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getImageStandardResolutionUrl(): string
    {
        return $this->imageStandardResolutionUrl;
    }

    /**
     * @param mixed $imageStandardResolutionUrl
     *
     * @return InstagramCarouselMedia
     */
    public function setImageStandardResolutionUrl($imageStandardResolutionUrl): InstagramCarouselMedia
    {
        $this->imageStandardResolutionUrl = $imageStandardResolutionUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getImageHighResolutionUrl(): string
    {
        return $this->imageHighResolutionUrl;
    }

    /**
     * @param mixed $imageHighResolutionUrl
     *
     * @return InstagramCarouselMedia
     */
    public function setImageHighResolutionUrl($imageHighResolutionUrl): InstagramCarouselMedia
    {
        $this->imageHighResolutionUrl = $imageHighResolutionUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVideoLowResolutionUrl()
    {
        return $this->videoLowResolutionUrl;
    }

    /**
     * @param mixed $videoLowResolutionUrl
     *
     * @return InstagramCarouselMedia
     */
    public function setVideoLowResolutionUrl($videoLowResolutionUrl): InstagramCarouselMedia
    {
        $this->videoLowResolutionUrl = $videoLowResolutionUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVideoStandardResolutionUrl()
    {
        return $this->videoStandardResolutionUrl;
    }

    /**
     * @param mixed $videoStandardResolutionUrl
     *
     * @return InstagramCarouselMedia
     */
    public function setVideoStandardResolutionUrl($videoStandardResolutionUrl): InstagramCarouselMedia
    {
        $this->videoStandardResolutionUrl = $videoStandardResolutionUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVideoLowBandwidthUrl()
    {
        return $this->videoLowBandwidthUrl;
    }

    /**
     * @param mixed $videoLowBandwidthUrl
     *
     * @return InstagramCarouselMedia
     */
    public function setVideoLowBandwidthUrl($videoLowBandwidthUrl): InstagramCarouselMedia
    {
        $this->videoLowBandwidthUrl = $videoLowBandwidthUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVideoViews()
    {
        return $this->videoViews;
    }

    /**
     * @param mixed $videoViews
     *
     * @return InstagramCarouselMedia
     */
    public function setVideoViews($videoViews): InstagramCarouselMedia
    {
        $this->videoViews = $videoViews;
        return $this;
    }

}
