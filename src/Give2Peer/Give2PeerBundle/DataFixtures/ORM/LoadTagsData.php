<?php

namespace Give2Peer\Give2PeerBundle\DataFixtures\ORM;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\DataFixtures\DataFixture;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\Tag;
use Symfony\Component\Yaml\Yaml;

/**
 * app/console doctrine:fixtures:load --env=test
 */
class LoadTagsData extends DataFixture
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $tags = "
- broken
- iron
- book
- wood
        ";

        $tags = Yaml::parse($tags);

        $i = 0;
        foreach ($tags as $tagName) {
            // Create a Tag
            $tag = new Tag();
            $tag->setName($tagName);

            // Add the item to database
            $em->persist($tag);

            print("Creating tag: ${tagName}       \r");

            // Flush batches of 11 tags
            if ($i % 11 == 1) $em->flush();
            $i++;
        }
        // Flush what remains
        $em->flush();

        print(sprintf("\n%d tags created.\n", count($tags)));
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder()
    {
        return 0;
    }
}