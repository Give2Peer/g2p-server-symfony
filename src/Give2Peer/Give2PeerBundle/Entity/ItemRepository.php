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

    /**
     * Counts the items that are currently published.
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
     * Counts all the items that were published since the beginning,
     * even the ones that were deleted.
     *
     * /!\
     * | Known bug : when the last inserted items are deleted,
     * | this is lower than it should be. It's okay for now.
     *
     * @return int
     */
    public function totalItems()
    {
        $total = $this->createQueryBuilder('i')
            ->select('MAX(i.id)') // replaces previous select in parent
            ->getQuery()
            ->execute()
            [0][1] // first column of first row holds the MAX
            ;
        if (empty($total)) { $total = 0; }

        return $total;
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
     * @return Item[]
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
        $total = $user->getAddItemsDailyQuota();

        $duration = new \DateInterval("P1D"); // 24h
        $since = (new \DateTime())->sub($duration);
        $filters = $this->getEntityManager()->getFilters();
        // We want to count the deleted items too
        $filters->disable('softdeleteable');
        $used = $this->countAuthoredBy($user, $since);
        $filters->enable('softdeleteable');

        return max(0, $total - $used);
    }

    /**
     * Soft delete the items that exceeded their lifespan in $seconds.
     *
     * @param  int $seconds Expected life duration in seconds of items
     * @return int Count of soft deleted items
     */
    public function softDeleteOldItems($seconds)
    {
        $duration = new \DateInterval("PT${seconds}S");
        $since = (new \DateTime())->sub($duration);

        // What we should do instead :
        // 1. Select the item IDs
        // 2. Delete the items
        // 3. Return the IDs

        $before = $this->countItems();

        // This hard deletes, as the softdeleteable filter has no power on the
        // DQL DELETE statement, it appears.
//        $this->getEntityManager()
//            ->createQueryBuilder()
//            ->delete($this->getEntityName(), 'i')
//            ->andWhere('i.updatedAt <= :since')
//            ->setParameter('since', $since)
//            ->getQuery()->execute()
//        ;

        // Instead we use this
        $this->getEntityManager()
            ->createQueryBuilder()
            ->update($this->getEntityName(), 'i')
            ->set('i.deletedAt', ':now')
            ->andWhere('i.updatedAt <= :since')
            ->setParameter('since', $since)
            ->setParameter('now', new \DateTime())
            ->getQuery()->execute()
        ;

        $after = $this->countItems();

        return $before - $after;
    }

    /**
     * Really and definitely delete from the database the items that were
     * soft-deleted more than $seconds ago.
     *
     * Disables the `softdeleteable` filter, obviously, even if it seems to not
     * hook the delete statement, which always hard-deletes. (bug ?)
     *
     * @param  int $seconds Expected afterlife duration in seconds of items
     * @return int Count of hard deleted items
     */
    public function hardDeleteOldItems($seconds)
    {
        $duration = new \DateInterval("PT${seconds}S");
        $since = (new \DateTime())->sub($duration);

        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('softdeleteable');

        // What we should probably do instead :
        // 1. Select the item IDs
        // 2. Delete the items
        // 3. Return the IDs

        $before = $this->countItems();

        // fixme: trouble !
        $this->getEntityManager()
            ->createQueryBuilder()
            ->delete($this->getEntityName(), 'i')
            ->andWhere('i.deletedAt <= :since')
            ->setParameter('since', $since)
            ->getQuery()->execute()
        ;

        $after = $this->countItems();
        $filters->enable('softdeleteable');

        return $before - $after;
    }

    /**
     * Really and definitely delete from the database the provided item.
     *
     * Disables the `softdeleteable` filter, obviously, even if it seems to not
     * hook the delete statement, which always hard-deletes. (bug ?)
     */
    public function hardDeleteItem(Item $item)
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('softdeleteable');

        $em = $this->getEntityManager();
        $em->remove($item);
        $em->flush();

        $filters->enable('softdeleteable');
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

        // Inject the additional `distance` property to items
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
            $qb->andWhere('DISTANCE(i.latitude, i.longitude, :latitude, :longitude) <= :maxDistance')
               ->setParameter('maxDistance', $maxDistance);
        }

        return $qb;
    }

}
