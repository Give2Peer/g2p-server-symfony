<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use FOS\UserBundle\Entity\User as BaseUser;
use Give2Peer\Give2PeerBundle\Entity\Item;

/**
 * A User of give2peer.
 *
 * User accounts are created as-needed by the mobile clients.
 * Users can later ascribe a proper username and password to save their account,
 * and be able to retrieve it from a different device.
 *
 * We use FOS User : https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Resources/doc/index.md
 *
 * User is a reserved word in SQL, so we use "peer" instead.
 * @ORM\Table(name="peer")
 * @ORM\Entity()
 */
class User extends BaseUser implements \JsonSerializable
{
    // Means that the user has meaningful password, email and username,
    // and has gone through the registration process.
//    const ROLE_REGISTERED = 'ROLE_REGISTERED'; // fixme: use property instead?

    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * I'm not sure we're using this. Why ?
     *
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'username' => $this->getUsernameCanonical(),
            'email' => $this->getEmailCanonical(),
            'created_at' => $this->getCreatedAt(),
        ];
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * A token for REST auth.
     * It should be refreshed, here's some ways to do it :
     * - CRON tasks (we'll need those eventually)
     * - ws RPC on disconnect() <= unreliable, as Ive observed, as clients don't
     *                             always run onleave events.
     * @ORM\Column(name="rest_token", type="string", length=32)
     */
    protected $restToken;

    /**
     * IP of the client that connected to the website and triggered the
     * automatic Guest user creation.
     * This should be used to banish abusive IPs.
     * Note that this contains the proxy IP if one was used.
     */
//    protected $createdBy;

    /**
     * @ORM\OneToMany(targetEntity="Item", mappedBy="giver")
     */
    protected $itemsGiven;

    /**
     * @ORM\OneToMany(targetEntity="Item", mappedBy="spotter")
     */
    protected $itemsSpotten;


    /**
     * Override of parent constructor to make sure we have a WS token ready.
     */
    public function __construct()
    {
        parent::__construct();
        $this->refreshRestToken();
    }

    /**
     * @return Item[]
     */
    public function getItemsGiven()
    {
        return $this->itemsGiven;
    }

    /**
     * @return String
     */
    public function getRestToken()
    {
        return $this->restToken;
    }

    /**
     * Refresh the token used to auth with WebSocket.
     * Will be a random md5.
     * /!\ I'M TRYING SOMETHING (the diacritics) HERE : MAY CRASH AND BURN.
     * @return String
     */
    public function refreshRestToken() {
        $abc = 'Dès Noël où un zéphyr haï me vêt de glaçons würmiens je dîne '.
               'd’exquis rôtis de bœuf au kir à l’aÿ d’âge mûr & cætera !';
        $this->restToken = md5($this->getPassword().'{'.str_shuffle($abc).'}');

        return $this->restToken;
    }

    /**
     * @return Item[]
     */
    public function getItemsSpotten()
    {
        return $this->itemsSpotten;
    }
}