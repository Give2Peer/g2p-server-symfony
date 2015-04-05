<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Geocoder\Result\Geocoded;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Give2Peer\Give2PeerBundle\Entity\User;

/**
 * Item
 *
 * This is a first-class citizen in the give2peer app.
 * It is the thing that is given, or spotted, and gathered.
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
            'title'       => $this->getTitle(),
            'location'    => $this->getLocation(),
            'latitude'    => $this->getLatitude(),
            'longitude'   => $this->getLongitude(),
            'description' => $this->getDescription(),
            'created_at'  => $this->getCreatedAt(),
            'updated_at'  => $this->getUpdatedAt(),
            'giver'       => $this->getGiver(),
            'spotter'     => $this->getSpotter(),
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
     * geolocalisation service in order to grab the actual numerical
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
    protected $owner;

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
     * Get location
     *
     * @return string 
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Item
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
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
        return $this->description;
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

    /**
     * @param User $owner
     * @return Item
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
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
     * Use Geocoder to fetch the latitude and longitude from our providers.
     * This throws if none can find anything.
     *
     * todo: refactor
     * This code should not be here, and locale and providers should be
     * easily configurable, as well as API keys.
     *
     * See https://github.com/geocoder-php/Geocoder/blob/2.x/README.md#api
     */
    public function geolocate() {

        $locale = 'fr_FR';

        $adapter  = new \Geocoder\HttpAdapter\BuzzHttpAdapter();
        $geocoder = new \Geocoder\Geocoder();
        $chain    = new \Geocoder\Provider\ChainProvider(array(
            new \Geocoder\Provider\FreeGeoIpProvider($adapter),
            new \Geocoder\Provider\HostIpProvider($adapter),
            new \Geocoder\Provider\OpenStreetMapProvider($adapter, $locale),
            new \Geocoder\Provider\GoogleMapsProvider($adapter, $locale, 'France', true),
        ));
        $geocoder->registerProvider($chain);

        /** @var Geocoded $geocode */
        $geocode = $geocoder->geocode($this->location);

        $this->latitude = $geocode->getLatitude();
        $this->longitude = $geocode->getLongitude();
    }
}
