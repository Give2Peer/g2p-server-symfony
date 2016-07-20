<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Thanks repository.
 */
class ThankRepository extends EntityRepository
{
    /**
     * Not overly optimized, but works.
     *
     * @param $user
     * @param $item
     * @return bool Whether or not the $user has thanked for this $item or not already.
     */
    public function hasUserThankedAlready($user, $item) {
        return (bool) $this->findOneBy([
            'thanker' => $user,
            'item'    => $item,
        ]);
    }
}
