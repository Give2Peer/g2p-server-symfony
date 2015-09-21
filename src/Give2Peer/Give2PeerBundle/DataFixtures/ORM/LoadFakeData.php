<?php

namespace Give2Peer\Give2PeerBundle\DataFixtures\ORM;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\DataFixtures\DataFixture;
use Give2Peer\Give2PeerBundle\Entity\Item;

/**
 * Fake it 'til you make it.
 *
 * Here's a fortune cookie :
 * When in panic, fear and doubt,
 * Drink in barrels, eat, and shout.
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
        /** @var \FOS\UserBundle\Entity\UserManager $um */
        $um = $this->get('fos_user.user_manager');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $userNames = [
            "Goutte",
            "Shibby",
            "Gizko",
            "Karibou",
            "Georges",
        ];

        print(sprintf("Creating a bunch of users : %s.\n", join(', ', $userNames)));

        $users = [];
        foreach ($userNames as $username) {
            $user = $um->createUser();
            $user->setEmail(strtolower($username).'@give2peer.org');
            $user->setUsername($username);
            // Hello, you... You found the passwords ! I have only one thing to
            // say : with great power comes great responsibility. :3
            $user->setPlainPassword($username);
            $user->setEnabled(true);

            // Canonicalize, encode, persist and flush
            $um->updateUser($user);

            $users[] = $user;
        }

        print("(their password is their username, don't tell anyone)\n");

        print("Creating randomly-positioned fake items in france.\n");

        $centerLatitude  = 46.605524;
        $centerLongitude = 2.422229;

        // bot: 42.514057, 2.641955
        // left: 46.628163, -0.906629

        $maxLatitudeDiff  = 4.0;
        $maxLongitudeDiff = 3.3;

        $total = 10101;
        // Let's create a bunch of items scattered through france
        for ($i=1; $i<=$total; $i++) {
            // Pick a location
            $latitude  = $centerLatitude  - $maxLatitudeDiff
                       + rand()/getrandmax() * $maxLatitudeDiff * 2;
            $longitude = $centerLongitude - $maxLongitudeDiff
                       + rand()/getrandmax() * $maxLongitudeDiff * 2;

            // Create the item
            $item = new Item();
            $item->setTitle(substr(sprintf("%s %s",
                $this->faker->colorName, $this->faker->word), 0, 32));
            $item->setDescription($this->faker->paragraph());
            $item->setLocation("$latitude, $longitude");
            $item->setLatitude($latitude);
            $item->setLongitude($longitude);

            switch (rand(0, 2)) {
                case 0:
                    $item->setGiver($users[array_rand($users)]);
                    break;
                case 1:
                    $item->setSpotter($users[array_rand($users)]);
                    break;
                case 2:
                default:
            }

            // Add the item to database
            $em->persist($item);

            // Flush batches of 1111 items (a totally arbitrary number)
            if ($i % 1111 == 1) $em->flush();

            print("${i} / ${total} items created.\r");
        }
        $em->flush();

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