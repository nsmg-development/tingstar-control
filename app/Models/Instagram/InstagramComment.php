<?php

namespace App\Models\Instagram;


class InstagramComment extends AbstractModel
{
    /**
     * @var
     */
    protected $id;

    /**
     * @var
     */
    protected $text;

    /**
     * @var
     */
    protected $createdAt;

    /**
     * @var InstagramAccount
     */
    protected $owner;

    /**
     * @var static[]
     */
    protected $childComments = [];

    /**
     * @var int
     */
    protected $childCommentsCount = 0;

    /**
     * @var bool
     */
    protected $hasMoreChildComments = false;

    /**
     * @var string
     */
    protected $childCommentsNextPage = '';

    /**
     * @var bool
     */
    protected bool $isLoaded = false;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return InstagramAccount
     */
    public function getOwner(): InstagramAccount
    {
        return $this->owner;
    }

    /**
     * @return static[]
     */
    public function getChildComments(): array
    {
        return $this->childComments;
    }

    /**
     * @return int
     */
    public function getChildCommentsCount(): int
    {
        return $this->childCommentsCount;
    }

    /**
     * @return bool
     */
    public function hasMoreChildComments(): bool
    {
        return $this->hasMoreChildComments;
    }

    /**
     * @return string
     */
    public function getChildCommentsNextPage(): string
    {
        return $this->childCommentsNextPage;
    }

    /**
     * @param $value
     * @param $prop
     */
    protected function initPropertiesCustom($value, $prop)
    {
        switch ($prop) {
            case 'id':
                $this->id = $value;
                break;
            case 'created_at':
                $this->createdAt = $value;
                break;
            case 'text':
                $this->text = $value;
                break;
            case 'owner':
                $this->owner = InstagramAccount::create($value);
                break;
            case 'edge_threaded_comments':
                if (isset($value['count'])) {
                    $this->childCommentsCount = (int) $value['count'];
                }
                if (isset($value['edges']) && is_array($value['edges'])) {
                    foreach ($value['edges'] as $commentData) {
                        $this->childComments[] = static::create($commentData['node']);
                    }
                }
                if (isset($value['page_info']['has_next_page'])) {
                    $this->hasMoreChildComments = (bool) $value['page_info']['has_next_page'];
                }
                if (isset($value['page_info']['end_cursor'])) {
                    $this->childCommentsNextPage = (string) $value['page_info']['end_cursor'];
                }
                break;
        }
    }

}
