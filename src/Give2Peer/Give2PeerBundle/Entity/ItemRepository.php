<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Give2Peer\Give2PeerBundle\Entity\User;

/**
 * The Item Repository is where most of the queries related to Items will be
 * located. This class should handle all the querying nitty-gritty and tweaks,
 * and provide dev-friendly methods with sugar on top.
 *
 * We enabled by default a `softdeleteable` filter on all Item queries.
 *
 * The SQL DISTANCE function is our custom function leveraging pgSQL's inner
 * functions of extension `earthdistance`.
 * See : http://www.postgresql.org/docs/9.2/static/earthdistance.html
 */
class ItemRepository extends EntityRepository
{
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Counts all items.
     *
     * @return int
     */
    public function countItems()
    {
        return $this->createQueryBuilder('i')
            ->select('COUNT(i)') // replaces previous select in parent
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
    public function countAuthoredBy(User $user, $since=null)
    {
        $qb = $this->authoredByQb($user, $since);

        return $qb->select('COUNT(i)')
                  ->getQuery()
                  ->execute()
                  [0][1] // first column of first row holds the COUNT
                  ;
    }

    /**
     * Finds all items that were created by $user, $since that time.
     *
     * @param  User      $user
     * @param  \Datetime $since
     * @return int
     */
    public function findAuthoredBy(User $user, $since=null)
    {
        $qb = $this->authoredByQb($user, $since);

        return $qb->getQuery()
                  ->execute();
    }

    protected function authoredByQb(User $user, $since=null)
    {
        $qb = $this->createQueryBuilder('i')
                   ->andWhere('i.author = :user')
                   ->setParameter('user', $user)
                   ;

        if (null != $since) {
            $qb->andWhere('i.createdAt >= :since')
                ->setParameter('since', $since)
            ;
        }

        return $qb;
    }

    /**
     * Computes what's left of the daily quota of the provided $user.
     * May be zero, but must never be negative.
     *
     * Disables the `softdeleteable` filter so as to take into account deleted
     * items.
     *
     * @param User $user
     * @return int
     */
    public function getAddItemsCurrentQuota(User $user)
    {
        $duration = new \DateInterval("P1D"); // 24h
        $since = (new \DateTime())->sub($duration);
        $filters = $this->getEntityManager()->getFilters();
        // We want to count the deleted items too
        $filters->disable('softdeleteable');
        $used = $this->countAuthoredBy($user, $since);
        $filters->enable('softdeleteable');
        $total = $user->getAddItemsDailyQuota();

        return max(0, $total - $used);
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
        /** @var Item[] $rows */
        $rows = $this->findAroundQB($latitude, $longitude, $skipTheFirstN,
            $maxDistance, $maxResults)
            ->getQuery()
            ->execute()
            ;
        // Ugly injection to provide the additional `distance` property to items
        foreach ($rows as $row) {
            $items[] = $row[0]->setDistance($row['distance']);
        }

        return $items;
    }

    public function findAroundQB($latitude, $longitude, $skipTheFirstN,
                                 $maxDistance, $maxResults)
    {
        $qb = $this->createQueryBuilder('i')
            ->addSelect('DISTANCE(i.latitude, i.longitude, :latitude, :longitude) AS distance')
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

}
