<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * An action of thanks, by the thanker to the thankee, about an item.
 * Some karma is involved : the thanker giveth, the thankee receiveth.
 *
 * The (thanker,item) tuple should be unique as per the specs : no thanking twice.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Give2Peer\Give2PeerBundle\Entity\ThankRepository")
 */
class Thank implements \JsonSerializable
{
    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        $json = [
            'id'             => $this->getId(),
            'created_at'     => $this->getCreatedAt()->format(DateTime::ISO8601),
            'thanker'        => $this->getThanker(),
            'thankee'        => $this->getThankee(),
            'item'           => $this->getItem(),
            'karma_received' => $this->getKarmaReceived(),
            'karma_given'    => $this->getKarmaGiven(),
        ];

        if ($this->getMessage() != null) {
            $json['message'] = $this->getMessage();
        }

        return $json;
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
     * The date and time (to the second) at which this thanks was infused.
     *
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * The user that thanked the other user for its item.
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="thanksGiven")
     * (this JoinColumn is unnecessary as those are the default values)
     * @ORM\JoinColumn(name="thanker_id", referencedColumnName="id")
     */
    private $thanker;

    /**
     * The user that was thanked for its item by the other user.
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="thanksReceived")
     * (this JoinColumn is unnecessary as those are the default values)
     * @ORM\JoinColumn(name="thankee_id", referencedColumnName="id")
     */
    private $thankee;

    /**
     * The user that was thanked for its item by the other user.
     *
     * @var Item
     *
     * @ORM\ManyToOne(targetEntity="Item", inversedBy="thanksReceived")
     * (this JoinColumn is unnecessary as those are the default values)
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id")
     */
    private $item;

    /**
     * A message you can only write if you're above the level ???
     * 
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=140, nullable=true)
     */
    private $message;

    /**
     * How much karma the thanker spent to say thanks.
     * 
     * @var int
     *
     * @ORM\Column(name="karma_given", type="integer")
     */
    private $karma_given = 0;

    /**
     * How much karma the thankee received.
     * Usually greater than the karma spent.
     * Greatness depends on user level.
     * 
     * @var int
     *
     * @ORM\Column(name="karma_received", type="integer")
     */
    private $karma_received = 0;
    
    

    ////////////////////////////////////////////////////////////////////////////

    public function __construct() {
        $this->items = new ArrayCollection();
    }

    /**
     * Get the unique identifier of this thank action. Pretty useless.
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
    public function getThanker()
    {
        return $this->thanker;
    }

    /**
     * @param User $thanker
     */
    public function setThanker($thanker)
    {
        $this->thanker = $thanker;
    }

    /**
     * @return User
     */
    public function getThankee()
    {
        return $this->thankee;
    }

    /**
     * @param User $thankee
     */
    public function setThankee($thankee)
    {
        $this->thankee = $thankee;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getKarmaGiven()
    {
        return $this->karma_given;
    }

    /**
     * @param int $karma_given
     */
    public function setKarmaGiven($karma_given)
    {
        $this->karma_given = $karma_given;
    }

    /**
     * @return int
     */
    public function getKarmaReceived()
    {
        return $this->karma_received;
    }

    /**
     * @param int $karma_received
     */
    public function setKarmaReceived($karma_received)
    {
        $this->karma_received = $karma_received;
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

}
