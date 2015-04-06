<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
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
        return $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u)')
            ->from($this->getEntityName(), 'u')
            ->where('u.createdBy = :created_by')
            ->setParameter('created_by', $ip)
            // fixme: use $since
            ->getQuery()
            ->execute()
            [0][1] // first column of first row
            ;
    }
}