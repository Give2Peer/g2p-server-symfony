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
 * We use FOS User :
 * https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Resources/doc/index.md
 *
 * User is a reserved word in SQL, so we use "peer" instead.
 * @ORM\Table(name="Peer")
 * @ORM\Entity(repositoryClass="Give2Peer\Give2PeerBundle\Entity\UserRepository")
 */
class User extends BaseUser implements \JsonSerializable
{
    // Means that the user has meaningful password, email and username,
    // and has gone through the registration process.
//    const ROLE_REGISTERED = 'ROLE_REGISTERED'; // fixme: use property instead?

    /**
     * Acceleration of Experience cost per level
     * Used as a constant in the formulas for levelling up.
     */
    const ACC_EXP_COST = 15;

    /**
     * Required Experience to be level 2.
     * Used as a constant in the formulas for levelling up.
     */
    const EXP_LVL_2 = 10;

    use ORMBehaviors\Timestampable\Timestampable;

    /**
     * Specify data which should be serialized to JSON and sent to the clients.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return [
            'id'         => $this->getId(),
            'username'   => $this->getUsernameCanonical(),
            'email'      => $this->getEmailCanonical(),
            'created_at' => $this->getCreatedAt(),
            'experience' => $this->getExperience(),
            'level'      => $this->getLevel(),
        ];
    }

    /**
     * Specify public data which should be serialized to JSON.
     */
    public function publicJsonSerialize()
    {
        $publicData = [
            'username'   => $this->getUsernameCanonical(),
            'level'      => $this->getLevel(),
        ];

        return $publicData;
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
     * Right now it is NOT USED, as we use brutal HttpAuth.
     * It should be refreshed, here's some ways to do it :
     * - CRON tasks (we'll need those eventually)
     *
     * @ORM\Column(name="rest_token", type="string", length=32)
     */
    protected $restToken;

    /**
     * IP of the client that registered.
     * This is used to banish abusive Users.
     * Note that this contains the proxy IP if one was used.
     *
     * @ORM\Column(name="created_by", type="string", nullable=true)
     */
    protected $createdBy;

    /**
     * The total sum of experience points this user has.
     * Experience points are gained automatically by using the service,
     * and are the building blocks of user levelling (up).
     *
     * @ORM\Column(name="experience", type="integer")
     */
    protected $experience = 0;

    /**
     * @ORM\OneToMany(targetEntity="Item", mappedBy="giver")
     */
    protected $itemsGiven;

    /**
     * @ORM\OneToMany(targetEntity="Item", mappedBy="spotter")
     */
    protected $itemsSpotten;


    // CONSTRUCTOR /////////////////////////////////////////////////////////////

    /**
     * Override of parent constructor to make sure we have a WS token ready.
     */
    public function __construct()
    {
        parent::__construct();
        $this->refreshRestToken();
    }


    // ITEMS ///////////////////////////////////////////////////////////////////

    /**
     * @return Item[]
     */
    public function getItemsGiven()
    {
        return $this->itemsGiven;
    }

    /**
     * @return Item[]
     */
    public function getItemsSpotten()
    {
        return $this->itemsSpotten;
    }


    // REST AUTHENTICATION TOKEN ///////////////////////////////////////////////
    // We don't use this, as we're using basic HTTP Authentication right now.

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


    // EXPERIENCE //////////////////////////////////////////////////////////////

    /**
     * @param int $experience to give to this user.
     */
    public function addExperience($experience)
    {
        $this->experience += max(0, (int) $experience);
    }

    /**
     * @param int $experience
     */
    public function setExperience($experience)
    {
        $this->experience = max(0, (int) $experience);
    }

    /**
     * @return int the total amount of experience points this user has.
     */
    public function getExperience()
    {
        return $this->experience;
    }

    /**
     * Experience points acquired towards next level.
     * This is the total of experience points minus the experience points
     * required to attain the current level of the user.
     *
     * @return int
     */
    public function getExperienceProgress()
    {
        return $this->experience - self::experienceOf($this->getLevel());
    }

    /**
     * @return int the amount of experience points missing to gain next level.
     */
    public function getExperienceMissing()
    {
        return self::experienceOf($this->getLevel()+1) - $this->experience;
    }

    /**
     * @return int the current level of this User.
     */
    public function getLevel()
    {
        return self::levelOf($this->experience);
    }

    /**
     * Should only be used when testing, as leveling up for users is
     * automatically done when adding experience with `addExperience`.
     *
     * @param int $level at which we want this user to be.
     */
    public function setLevel($level)
    {
        $this->setExperience(self::experienceOf($level));
    }

    /**
     * @thanks Aurel Page for the formula.
     *
     * @param  int $experience
     * @return int the level attained with $experience points.
     */
    static function levelOf($experience)
    {
        $a = self::ACC_EXP_COST;
        $d = self::EXP_LVL_2;
        $n = floor(
            (3 * $a - 2 * $d + sqrt(pow(2 * $d - $a, 2) + 8 * $a * $experience))
            /
            (2 * $a)
        );

        return max($n, 1);
    }

    /**
     * @thanks Aurel Page for the formula.
     *
     * @param  int $level
     * @return int the experience required to be $level.
     */
    static function experienceOf($level)
    {
        $a = self::ACC_EXP_COST;
        $d = self::EXP_LVL_2;
        $n = $level;

        return ($d - $a) * ($n - 1) + $a * ($n * $n - $n) / 2;
    }


    // BORING STUFF ////////////////////////////////////////////////////////////

    /**
     * @return String|null
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param String|null $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

}
