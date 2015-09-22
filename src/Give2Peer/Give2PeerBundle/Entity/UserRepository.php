<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class UserRepository extends EntityRepository
{
    /**
     * Query builder to count all users.
     *
     * @return QueryBuilder
     */
    public function countUsersQb()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u)')
            ->from($this->getEntityName(), 'u')
            ;
    }

    /**
     * Count all users.
     *
     * @return int
     */
    public function countUsers()
    {
        return $this->countUsersQb()
            ->getQuery()
            ->execute()
            [0][1] // first column of first row
            ;
    }

    /**
     * Count all users that were created by the provided $ip, $since that time.
     *
     * @param $ip    string
     * @param $since \DateTime
     * @return int
     */
    public function countUsersCreatedBy($ip, $since)
    {
        return $this->countUsersQb()
            ->where('u.createdBy = :created_by')
            ->andWhere('u.createdAt >= :since')
            ->setParameter('created_by', $ip)
            ->setParameter('since', $since)
            ->getQuery()
            ->execute()
            [0][1] // first column of first row
            ;
    }
}