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
 * This is a first-class citizen in the give2peer app.
 * It is the thing that is given, or spotted, and gathered.
 *
 * We try to be verbose in the ORM annotations in order to not rely too much on
 * their default values and provide cheap snippets for future improvements.
 * This is a strategy I seldom use but here it feels weirdly appropriate.
 * Besides, it's all cached so it has no effect on production performance.
 *
 * Note : about giver and spotter (and possibly owner)
 *   Maybe I should just drop it and use a single `author` field.
 *   It feels too weird to have two separate fields that can never be both set.
 *   I don't see the benefit anymore. This is a paper cut.
 *   But `author` does not feel good either. It's not authorship of the Item
 *   itself, only of its symbolic model in our information structure.
 *   `painter`, `tagger` ... WTF! `tagger` is fine ! `tagger` it is !
 *   fixme: refactor both giver and spotter into tagger.
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
            'giver'       => $this->getGiver(),
            'spotter'     => $this->getSpotter(),
        );
    }

    /**
     * http://komlenic.com/244/8-reasons-why-mysqls-enum-data-type-is-evil/
     *
     * gift : giver legally owns the item and gives it for free
     * lost : spotter just shares the location of the item
     * moop : Matter Out Of Place, everything else.
     */
    const TYPE_GIFT = 'gift';
    const TYPE_LOST = 'lost';
    const TYPE_MOOP = 'moop'; // default

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
     * This may also contain IP addresses.
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
     * We enrich the Item with this property (when relevant) right before
     * sending it in the response, but this is not stored in the database for
     * obvious reasons. This is just sugar from the server to their clients.
     *
     * @var float
     */
    private $distance;

    /**
     * One of `Item::TYPES` : gift, lost, moop.
     * This will be useful for filtering queries.
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
     * This is the User that legally owned this Item and transferred its legal
     * ownership to somebody else.
     * May be empty if there is no giver, but a spotter.
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="itemsGiven")
     * @ORM\JoinColumn(name="giver_id", referencedColumnName="id")
     */
    protected $giver;

    /**
     * May be empty if there is no spotter, but a giver.
     * This is a passerby that spotted the Item in a context where it appears
     * that the Item has no legal owner anymore. (close to the garbage bins, for
     * example)
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="itemsSpotten")
     * @ORM\JoinColumn(name="spotter_id", referencedColumnName="id")
     */
    protected $spotter;

    /**
     * We don't use this right now. In the future, maybe ?
     *
     * This is the current legal owner of this Item. May be nobody.
     * This property will change through time, as an Item's ownership is
     * transferred :
     * - from a giver to a gatherer
     * - from a giver to nobody     (abandon)
     * - from nobody to a gatherer  (public spot)
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="itemsOwned")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     */
//    protected $owner;


    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

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
     * Set location
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
     * Get location, a string that can be pretty much anything that our
     * third-party geolocating services can reduce to a lat/lng set.
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
     */
    public function setType($type)
    {
        if ( ! in_array($type, array(
            self::TYPE_GIFT, self::TYPE_LOST, self::TYPE_MOOP
        ))) throw new \InvalidArgumentException("Invalid type.");

        $this->type = $type;
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
     * Set title
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
     */
    public function addTag(Tag $tag)
    {
        $tag->addItem($this); // synchronously update the inverse side
        $this->tags[] = $tag;
    }

    /**
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $tag->removeItem($this); // synchronously update the inverse side
        $this->tags->removeElement($tag);
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
     * @param User $giver
     * @return Item
     */
    public function setGiver($giver)
    {
        $this->giver = $giver;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getGiver()
    {
        return $this->giver;
    }

    /**
     * @param User $spotter
     * @return Item
     */
    public function setSpotter($spotter)
    {
        $this->spotter = $spotter;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getSpotter()
    {
        return $this->spotter;
    }

//    /**
//     * @param User $owner
//     * @return Item
//     */
//    public function setOwner($owner)
//    {
//        $this->owner = $owner;
//
//        return $this;
//    }
//
//    /**
//     * @return User
//     */
//    public function getOwner()
//    {
//        return $this->owner;
//    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
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
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return float
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
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
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }
}
