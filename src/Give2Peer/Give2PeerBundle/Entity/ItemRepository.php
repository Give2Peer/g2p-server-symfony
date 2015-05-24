<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ItemRepository
 *
 * The SQL DISTANCE function is our custom function leveraging pgSQL's inner
 * functions of extension `earthdistance`.
 *
 * See : http://www.postgresql.org/docs/9.2/static/earthdistance.html
 */
class ItemRepository extends EntityRepository
{
    /**
     * List items by increasing distance to provided `$latitude`/`$longitude`.
     * You can paginate by skipping results and setting a max limit to the
     * number of results you want.
     *
     * Returns an array of Items, including the additional `distance` property.
     *
     * @param $latitude
     * @param $longitude
     * @param int $skipTheFirstN
     * @param int $maxDistance
     * @param int $maxResults
     * @return mixed
     */
    public function findAround($latitude, $longitude, $skipTheFirstN=0,
                               $maxDistance=0, $maxResults=128)
    {
        $items = [];
        $rows = $this->findAroundQB($latitude, $longitude, $skipTheFirstN,
            $maxDistance, $maxResults)
            ->getQuery()
            ->execute()
            ;
        foreach ($rows as $row) {
            $items[] = $row[0]->setDistance($row['distance']);
        }
        return $items;
    }

    public function findAroundQB($latitude, $longitude, $skipTheFirstN,
                                 $maxDistance, $maxResults)
    {
        $qb = $this->createQueryBuilder('e')
            ->addSelect('DISTANCE(e.latitude, e.longitude, :latitude, :longitude) AS distance')
            ->addOrderBy('distance')
            ->setFirstResult($skipTheFirstN)
            ->setMaxResults($maxResults)
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ;
        if ($maxDistance > 0)
            $qb->andWhere('distance <= :maxDistance')
               ->setParameter('maxDistance', $maxDistance);
        return $qb;
    }

    public function findByDistanceQB($latitude, $longitude, $distanceMax)
    {
        return $this->createQueryBuilder('e')
            ->addSelect('DISTANCE(e.latitude, e.longitude, :latitude, :longitude) AS distance')
            ->andWhere('DISTANCE(e.latitude, e.longitude, :latitude, :longitude) <= :distanceMax')
            ->addOrderBy('distance')
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
