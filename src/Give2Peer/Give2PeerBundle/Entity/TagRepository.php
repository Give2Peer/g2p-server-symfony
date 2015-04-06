<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Tag Repository
 *
 * Some users will be able to add new tags
 */
class TagRepository extends EntityRepository
{
    /**
     * Get tags whose name is in $tagnames.
     *
     * @param string[] $tagnames
     * @return Tag[]
     */
    public function findTags($tagnames)
    {
        return $this->findTagsQB($tagnames)
            ->getQuery()
            ->execute()
            ;
    }

    public function findTagsQB($tagnames)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.name IN (:tagnames)')
            ->setParameter('tagnames', array_values($tagnames))
            ;
    }
}
