<?php

namespace Give2Peer\Give2PeerBundle\Entity\Behavior;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

// Our setters `return $this` but this trait is for multiple entities.
// How else to maintain auto-completion derived from annotations ?
// This is not good because we need to update the annots in this trait
// ... but if we forget, only auto-completion is broken.
// ... how to solve this ?
// ... maybe upstream with some magic in the annots, like @return $this ?
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\User;


/**
 * Replaces
 * Knp\DoctrineBehaviors\Model\Timestampable\Timestampable
 * to ensure that created SQL table fields are snake_case.
 * 
 * We use Gedmo :
 * https://github.com/Atlantic18/DoctrineExtensions/blob/master/doc/timestampable.md
 *
 * Timestampable needs to be enabled in the config.yml too.
 */
trait GedmoTimestampable
{
    /**
     * The date and time (to the second) at which this item was created.
     *
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * The date and time (to the second) at which this item was last updated.
     *
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;


    /**
     * The date and time (to the second) at which this item was created.
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set the date and time (to the second) of the creation of this item.
     *
     * Don't bother, our ORM hooks handle setting this on creation for us.
     *
     * If you're actually using this, you're probably writing kickass features.
     *
     * @param DateTime $createdAt
     * @return Item|User
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * The date and time (to the second) at which this item was last updated.
     *
     * Any change in any field of this Item will refresh this value.
     *
     * Our ORM hooks handle refreshing this on update for us.
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set the date and time (to the second) when this item was last updated.
     *
     * Don't bother, our ORM hooks handle refreshing this on update for us.
     *
     * @param DateTime $updatedAt
     * @return Item|User
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}