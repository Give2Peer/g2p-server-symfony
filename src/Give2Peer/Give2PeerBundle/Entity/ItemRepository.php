<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ItemRepository
 *
 * DISTANCE function is our custom function leveraging pgSQL's inner functions.
 *
 */
class ItemRepository extends EntityRepository
{
    public function findByDistanceQB($latitude, $longitude, $distanceMax)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('DISTANCE(e.latitude, e.longitude, :latitude, :longitude) <= :distanceMax')
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->setParameter('distanceMax', $distanceMax)
            ;
    }

    public function findByDistance($latitude, $longitude, $distanceMax)
    {
        return $this->findByDistanceQB($latitude, $longitude, $distanceMax)
            ->getQuery()
            ->execute()
            ;
    }
}
