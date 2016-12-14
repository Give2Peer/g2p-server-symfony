<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ItemPictureRepository extends EntityRepository
{
    /**
     * Our Doctrine preRemove hook defined in the ItemPainter will handle the
     * deletion of the actual files.
     *
     * @param  int $seconds Maximum lifetime in seconds of orphan item pictures.
     * @return array Ids of the deleted item pictures.
     */
    public function deleteOldOrphans($seconds)
    {
        $duration = new \DateInterval("PT${seconds}S");
        $since = (new \DateTime())->sub($duration);

        $ids = [];

        $em = $this->getEntityManager();

        $pics = $em->createQueryBuilder()
            ->select('i')
            ->distinct()
            ->from($this->getEntityName(), 'i')
            ->where('i.createdAt <= :since')
            ->setParameter('since', $since)
            ->getQuery()->execute()
        ;

        foreach ($pics as $pic) {
            /** @var ItemPicture $pic */
            $ids[] = $pic->getId();
            // We need to use remove() and not a DQL query, to trigger the hooks
            $em->remove($pic);
        }

        return $ids;
    }
}
