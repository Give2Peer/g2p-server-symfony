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
    public function findAroundQB($latitude, $longitude, $skipTheFirstN, $maxResults)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('DISTANCE(e.latitude, e.longitude, :latitude, :longitude) AS distance')
            ->addOrderBy('distance')
            ->setFirstResult($skipTheFirstN)
            ->setMaxResults($maxResults)
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ;
    }

    /**
     * List items by increasing distance to provided `$latitude`/`$longitude`.
     * You can paginate by skipping results and setting a max limit to the
     * number of results you want.
     *
     * @param $latitude
     * @param $longitude
     * @param $skipTheFirstN
     * @param $maxResults
     * @return mixed
     */
    public function findAround($latitude, $longitude, $skipTheFirstN=0, $maxResults=32)
    {
        return $this->findAroundQB($latitude, $longitude, $skipTheFirstN, $maxResults)
            ->getQuery()
            ->execute()
            ;
    }

    public function findByDistanceQB($latitude, $longitude, $distanceMax)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('DISTANCE(e.latitude, e.longitude, :latitude, :longitude) AS distance')
            ->andWhere('DISTANCE(e.latitude, e.longitude, :latitude, :longitude) <= :distanceMax')
            ->addOrderBy('distance', 'DESC')
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
