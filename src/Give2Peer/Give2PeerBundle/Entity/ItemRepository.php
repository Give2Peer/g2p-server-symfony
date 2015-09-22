<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Give2Peer\Give2PeerBundle\Entity\User;

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
     * Get a QueryBuilder to counts all items.
     *
     * @return QueryBuilder
     */
    public function countItemsQb()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(i)')
            ->from($this->getEntityName(), 'i')
            ;
    }

    /**
     * Counts all items.
     *
     * @return int
     */
    public function countItems()
    {
        return $this->countItemsQb()
            ->getQuery()
            ->execute()
            [0][1] // first column of first row holds the COUNT
            ;
    }

    /**
     * Counts all items that were created by $user, $since that time.
     *
     * @param  User      $user
     * @param  \Datetime $since
     * @return int
     */
    public function countItemsCreatedBy(User $user, $since)
    {
        return $this->countItemsQb()
            ->where('i.giver = :user OR i.spotter = :user')
            ->andWhere('i.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->getQuery()
            ->execute()
            [0][1] // first column of first row holds the COUNT
            ;
    }

    /**
     * List items by increasing distance to provided `$latitude` & `$longitude`.
     * You can paginate by skipping results and setting a max limit to the
     * number of results you want.
     *
     * Returns an array of Items, including the additional `distance` property.
     *
     * @param  float $latitude
     * @param  float $longitude
     * @param  int   $skipTheFirstN
     * @param  int   $maxDistance
     * @param  int   $maxResults
     * @return mixed
     */
    public function findAround($latitude, $longitude, $skipTheFirstN=0,
                               $maxDistance=0, $maxResults=64)
    {
        $items = [];
        $rows = $this->findAroundQB($latitude, $longitude, $skipTheFirstN,
            $maxDistance, $maxResults)
            ->getQuery()
            ->execute()
            ;
        // Ugly hack to provide the additional `distance` property to items
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
        if ($maxDistance > 0) {
            $qb->andWhere('distance <= :maxDistance')
               ->setParameter('maxDistance', $maxDistance);
        }

        return $qb;
    }

//    public function findByDistanceQB($latitude, $longitude, $distanceMax)
//    {
//        return $this->createQueryBuilder('e')
//            ->addSelect('DISTANCE(e.latitude, e.longitude, :latitude, :longitude) AS distance')
//            ->andWhere('DISTANCE(e.latitude, e.longitude, :latitude, :longitude) <= :distanceMax')
//            ->addOrderBy('distance')
//            ->setParameter('latitude', $latitude)
//            ->setParameter('longitude', $longitude)
//            ->setParameter('distanceMax', $distanceMax)
//            ;
//    }
//
//    public function findByDistance($latitude, $longitude, $distanceMax)
//    {
//        return $this->findByDistanceQB($latitude, $longitude, $distanceMax)
//            ->getQuery()
//            ->execute()
//            ;
//    }
}
