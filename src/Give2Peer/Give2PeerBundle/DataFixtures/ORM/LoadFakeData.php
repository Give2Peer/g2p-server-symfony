<?php

namespace Give2Peer\Give2PeerBundle\DataFixtures\ORM;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\DataFixtures\DataFixture;
use Give2Peer\Give2PeerBundle\Entity\Item;

/**
 * Fake it 'til you make it.
 *
 * app/console doctrine:fixtures:load --env=test
 */
class LoadFakeData extends DataFixture
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        print("Creating randomly-positioned fake items in france.\n");

        $centerLatitude  = 46.605524;
        $centerLongitude = 2.422229;

        // bot: 42.514057, 2.641955
        // left: 46.628163, -0.906629

        $maxLatitudeDiff  = 4.0;
        $maxLongitudeDiff = 3.3;

        $total = 10000;
        // Let's create a million items scattered through france
        for ($i=0; $i<$total; $i++) {
            // Pick a location
            $latitude  = $centerLatitude  - $maxLatitudeDiff
                       + rand()/getrandmax() * $maxLatitudeDiff;
            $longitude = $centerLongitude - $maxLongitudeDiff
                       + rand()/getrandmax() * $maxLongitudeDiff;

            // Create the item
            $item = new Item();
            $item->setTitle(sprintf("%s %s",
                $this->faker->colorName, $this->faker->word));
            $item->setDescription($this->faker->paragraph());
            $item->setLocation("$latitude, $longitude");
            $item->setLatitude($latitude);
            $item->setLongitude($longitude);

            // Add the item to database
            /** @var EntityManager $em */
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($item);
            $em->flush();

            print("${i} / ${total} items created.\r");
        }

        print("\n");
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1;
    }
}