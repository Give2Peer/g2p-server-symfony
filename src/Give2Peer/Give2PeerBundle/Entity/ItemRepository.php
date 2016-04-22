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
 * The SQL DISTANCE function is our custom function leveraging pgSQL's inner
 * functions of extension `earthdistance`.
 *
 *
 * /!\
 * Use should use softdeleteable instead of writing methods such as authoredBy()
 * This would remove lots of code in here, and that would be good ©.
 *
 *
 *
 * See : http://www.postgresql.org/docs/9.2/static/earthdistance.html
 */
class ItemRepository extends EntityRepository
{
    const ITEM_QUERY_ALIAS = 'i';

    /**
     * We should make most of our methods with this qb, as it allows us to
     * exclude the items marked for deletion.
     *
     * fixme: use SoftDeletable instead !
     * https://github.com/Atlantic18/DoctrineExtensions/blob/master/doc/softdeleteable.md
     *
     * @param bool $exclude_deleted
     * @return QueryBuilder
     */
    public function createQueryBuilder($exclude_deleted=true)
    {
        $qb = parent::createQueryBuilder(self::ITEM_QUERY_ALIAS);
        
        if ($exclude_deleted) {
            // Equivalent
            //$qb = $qb->andWhere('i.deletedAt IS NULL');
            $qb = $qb->andWhere($qb->expr()->isNull('i.deletedAt'));
        }
        
        return $qb;
    }

    ////////////////////////////////////////////////////////////////////////////

    /**
     * Get a QueryBuilder to counts all items.
     *
     * @return QueryBuilder
     */
    public function countItemsQb($exclude_deleted=true)
    {
        return $this->createQueryBuilder($exclude_deleted)
                    ->select('COUNT(i)') // replaces previous select in parent
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
     * This methods makes sense because it provdes the `since` parameter for
     * convenience, but the `exclude_deleted` parameter should NOT be here.
     *
     * @param  User      $user
     * @param  \Datetime $since
     * @return int
     */
    public function countAuthoredBy(User $user, $since=null, $exclude_deleted=true)
    {
        $qb = $this->authoredByQb($user, $since, $exclude_deleted);

        return $qb->select('COUNT(i)')
                  ->getQuery()
                  ->execute()
                  [0][1] // first column of first row holds the COUNT
                  ;
    }

    /**
     * Finds all items that were created by $user, $since that time.
     *
     * This methods makes sense because it provdes the `since` parameter for
     * convenience, but the `exclude_deleted` parameter should NOT be here.
     *
     * @param  User      $user
     * @param  \Datetime $since
     * @return int
     */
    public function findAuthoredBy(User $user, $since=null, $exclude_deleted=true)
    {
        $qb = $this->authoredByQb($user, $since, $exclude_deleted);

        return $qb->getQuery()
                  ->execute();
    }

    protected function authoredByQb(User $user, $since=null, $exclude_deleted=true)
    {
        $qb = $this->createQueryBuilder($exclude_deleted)
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
     * @param User $user
     * @return int
     */
    public function getAddItemsCurrentQuota(User $user)
    {
        $duration = new \DateInterval("P1D"); // 24h
        $since = (new \DateTime())->sub($duration);
        $used = $this->countAuthoredBy($user, $since, false); // deleted too
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
        $qb = $this->createQueryBuilder()
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
