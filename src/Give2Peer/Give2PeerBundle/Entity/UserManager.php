<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use FOS\UserBundle\Doctrine\UserManager as BaseUserManager;

class UserManager extends BaseUserManager
{
    /**
     * Counts all users that were created by the provided $ip, $since that time.
     *
     * @param $ip    string
     * @param $since \DateTime
     * @return int
     */
    public function countUsersCreatedBy($ip, $since)
    {
        return $this->repository->countUsersCreatedBy($ip, $since);
    }
}