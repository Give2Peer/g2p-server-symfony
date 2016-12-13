<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Exception;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * fixme
 *
 * A single item picture uploaded by the user, that we converted to JPG,
 * cropped if it was too big, and created a JPG 200x200 thumbnail out of.
 *
 * It should be attached to an Item, but it may not, temporarily.
 * This allows clients to pre-upload pictures while the user is still filling
 * out the item name and other attributes, for a smoother user experience.
 * Orphan pictures are deleted by the CRON task after 24h.
 * Deleting an item should also delete all its associated pictures, which in
 * turn should delete their files, ie. the actual picture and all of its
 * generated thumbnails.
 *
 * The actual JPG files are stored in a publicly available directory, not in the
 * database itself.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Give2Peer\Give2PeerBundle\Entity\ItemPictureRepository")
 */
class ItemPicture implements \JsonSerializable
{
    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return [
            'id'         => $this->getId(),
            'url'        => $this->getUrl(),
            'thumbnails' => $this->getThumbnailsUrls(),
        ];
    }

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * The date and time (to the second) at which this picture was uploaded.
     * This is used to enforce picture upload quotas for users.
     * It could also be named $uploadedAt, but... consistency ?!
     *
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * The user that uploaded this picture.
     * If the author is deleted, this picture should *not* be.
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="itemPicturesUploaded")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id")
     */
    private $author;

    /**
     * The item this picture is a picture of. May be null.
     * In that case, it means this picture is an orphan and it will be deleted
     * automatically by our cleanup CRON routine after 24h of existence.
     * When the item is deleted, all of its pictures will be deleted too.
     *
     * @var Item
     *
     * @ORM\ManyToOne(targetEntity="Item", inversedBy="pictures")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $item;

    /**
     * The full URL to this picture.
     * This is not stored in the database, but injected by the ItemPainter.
     * @var String
     */
    private $url;

    /**
     * An array of the full URLs to the thumbnails.
     * This is not stored in the database, but injected by the ItemPainter.
     * @var String[]
     */
    private $thumbnailsUrls;


    public function __construct() {}


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param User $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param Item $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * @return String
     * @throws Exception
     */
    public function getUrl()
    {
        if (null == $this->url) {
            throw new Exception(
                "Use ItemPainter.injectUrl() on this item picture first."
            );
        }

        return $this->url;
    }

    /**
     * @param String $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return String[]
     * @throws Exception
     */
    public function getThumbnailsUrls()
    {
        if (null == $this->thumbnailsUrls) {
            throw new Exception(
                "Use ItemPainter.injectUrl() on this item picture first."
            );
        }

        return $this->thumbnailsUrls;
    }

    /**
     * @param String[] $urls
     */
    public function setThumbnailsUrls($urls)
    {
        $this->thumbnailsUrls = $urls;
    }

}
