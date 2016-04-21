<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Geocoder\Result\Geocoded;
use Give2Peer\Give2PeerBundle\Provider\LatitudeLongitudeProvider;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Give2Peer\Give2PeerBundle\Entity\User;

/**
 * Item
 *
 * This is a first-class citizen in the give2peer service architecture.
 * It is the thing that is given, or spotted, and then gathered or discarded.
 *
 * We try to be verbose in the ORM annotations in order to not rely too much on
 * their default values and provide cheap snippets for future improvements.
 * This is a strategy I seldom use but here it feels weirdly appropriate.
 * There's no auto-completion in annotations (yet).
 * Besides, it's all cached so it has no effect on production performance.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Give2Peer\Give2PeerBundle\Entity\ItemRepository")
 */
class Item implements \JsonSerializable
{
    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return array(
            'id'          => $this->getId(),
            'type'        => $this->getType(),
            'title'       => $this->getTitle(),
            'location'    => $this->getLocation(),
            'latitude'    => $this->getLatitude(),
            'longitude'   => $this->getLongitude(),
            'distance'    => $this->getDistance(),
            'description' => $this->getDescription(),
            'thumbnail'   => $this->getThumbnail(),
            'tags'        => $this->getTagnames(),
            'created_at'  => $this->getCreatedAt()->format(DateTime::ISO8601),
            'updated_at'  => $this->getUpdatedAt()->format(DateTime::ISO8601),
            'author'      => $this->getAuthor(),
        );
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * This is the full location, in any form that we can feed to the
     * geolocation service in order to grab the actual numerical
     * coordinates.
     *
     * This may also contain IP addresses, but it SHOULD not, it's not reliable.
     *
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=512)
     */
    private $location;

    /**
     * The latitude of the object.
     *
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", nullable=true)
     */
    private $latitude;

    /**
     * The longitude of the object.
     *
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", nullable=true)
     */
    private $longitude;

    /**
     * When we serialize Items in JSON, we usually want to provide the distance
     * at which the item is. This property is obviously highly dynamic.
     * We inject the Item with this property (when relevant) right before
     * sending it in the response, but this is not stored in the database for
     * obvious reasons. This is just sugar from the server to their clients.
     *
     * @var float
     */
    private $distance;



    /**
     * This may be handled by tags, or seen as system tags, as tags may be
     * provided by the community later on.
     *
     * We consider these as exclusive, even if they're all MOOP, technically.
     *
     * We use constants and a string field instead of an ENUM.
     * http://komlenic.com/244/8-reasons-why-mysqls-enum-data-type-is-evil/
     * Our solution is not much better, but it can evolve rather more easily
     * towards a foreign key, to an `item_type` table, say.
     *
     * gift : giver legally owns the item and gives it for free
     * lost : spotter just shares the location of the item
     * moop : Matter Out Of Place, everything else, the default type.
     */
    const TYPE_GIFT = 'gift';
    const TYPE_LOST = 'lost';
    const TYPE_MOOP = 'moop'; // default

    /**
     * One of `Item::TYPES` : `gift`, `lost`, or the default : `moop`.
     * This will be useful for filtering queries.
     *
     * STILL NOT SURE THIS IS THE BEST DESIGN PATTERN.
     * Maybe we should use tags instead ?
     * What does this provide that tags don't ?
     * ...
     * This can evolve into a foreign key to a `TYPE` table, which could in turn
     * hold configuration about the various item types, such as color, how high
     * is the limit of items shown on the map, things like these.
     * ...
     * Tags may also hold such information. Hmmm... Time to go for a walk.
     * 
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=4)
     */
    private $type = self::TYPE_MOOP;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=32, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=1024, nullable=true)
     */
    private $description;

    /**
     * The full URL to the thumbnail image file.
     * It should be 200px x 200px.
     *
     * @var string
     *
     * @ORM\Column(name="thumbnail", type="string", length=2048, nullable=true)
     */
    private $thumbnail;

    /**
     * Tags are easy to select, and allow for nice hunting filters.
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="items")
     * @ORM\JoinTable(name="items_tags")
     */
    private $tags;

    /**
     * The user that inserted this item into the database.
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="itemsAuthored")
     * (this JoinColumn is unnecessary as those are the default values)
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id")
     */
    protected $author;

    ////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        $this->tags = new ArrayCollection(); // Y U NO [] ? => Useful methods !
    }

    /**
     * Get the unique id of this user.
     * It is automatically set upon registration and cannot be changed.
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set location, a string that can be pretty much anything that our custom &
     * third-party geolocating services can reduce to a lat/lng set.
     *
     * Most clients should provide a lat/lng set from GPS, but sometimes a user
     * will want to enter a postal address, that's when the third-party services
     * are called in. They're subject to quotas.
     *
     * Usage of this method is safe as it does not
     *
     * @param string $location
     * @return Item
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location, a string that can be pretty much anything that our custom &
     * third-party geolocating services can reduce to a lat/lng set.
     *
     * Most clients should provide a lat/lng set from GPS, but sometimes a user
     * will want to enter a postal address, that's when the third-party services
     * are used.
     *
     * @return string 
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * One of :
     * - gift
     * - lost
     * - moop (default)
     *
     * @param $type
     * @return Item
     */
    public function setType($type)
    {
        if ( ! in_array($type, array(
            self::TYPE_GIFT, self::TYPE_LOST, self::TYPE_MOOP
        ))) throw new \InvalidArgumentException("Invalid type.");

        $this->type = $type;

        return $this;
    }

    /**
     * One of :
     * - gift
     * - lost
     * - moop (default)
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the title of this item, truncating it to 32 characters beforehand.
     *
     * @param string $title
     * @return Item
     */
    public function setTitle($title)
    {
        $this->title = mb_substr($title, 0, 32);

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        if (null == $this->title) return "";

        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Item
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        if (null == $this->description) return "";

        return $this->description;
    }

    /**
     * @param Tag $tag
     * @return Item
     */
    public function addTag(Tag $tag)
    {
        $tag->addItem($this); // synchronously update the inverse side
        $this->tags->add($tag);

        return $this;
    }

    /**
     * @param Tag $tag
     * @return Item
     */
    public function removeTag(Tag $tag)
    {
        $tag->removeItem($this); // synchronously update the inverse side
        $this->tags->removeElement($tag);

        return $this;
    }

    /**
     * @return ArrayCollection of Tag
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return string[]
     */
    public function getTagnames()
    {
        $names = [];
        foreach ($this->tags as $tag) {
            $names[] = $tag->getName();
        }

        return $names;
    }

    /**
     * @return User|null
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param User $author
     * @return Item
     */
    public function setAuthor($author)
    {
        // Don't update the inverse side of the relationship
        
        $this->author = $author;

        return $this;
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     * @return Item
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     * @return Item
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get the distance along the great circle of Earth in meters between this
     * item and the location provided by the user that queried it.
     *
     * This property is NOT loaded from the database but injected into the Item
     * after some queries, and may NOT be present.
     *
     * @return float
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * Injection method. Used by some queries to inject the `distance` property.
     *
     * Distance should be in meters along the great circle of Earth.
     *
     * @param float $distance
     * @return Item
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;

        return $this;
    }

    /**
     * @return string
     */
    public function getThumbnail()
    {
        if (null == $this->thumbnail) return "";

        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     * @return Item
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }
}
