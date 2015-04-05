<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

class RestController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        // todo: why not provide some documentation here ?
        return $this->render('Give2PeerBundle:Default:index.html.twig');
    }

    /**
     * A Giver is the legal owner of the item.
     *
     * Item attributes can be provided as POST variables :
     *   - location (mandatory)
     *   - title
     *   - description
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function giveAction(Request $request)
    {
        return $this->giveOrSpotAction($request, true);
    }

    /**
     * A Spotter does not own the Item, which is probably just lying around in
     * public space.
     *
     * See `give`.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function spotAction(Request $request)
    {
        return $this->giveOrSpotAction($request, false);
    }

    /**
     * Returns a list of at most 10 Items, sorted by increasing distance to
     * the center of the circle.
     *
     *
     *
     *
     * @param float $latitude Latitude of the center of the circle.
     * @param float $longitude Longitude of the center of the circle.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listByCoordinatesAction($latitude, $longitude)
    {
        // fixme

        $latitude = floatval($latitude);
        $longitude = floatval($longitude);

        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        $con = $em->getConnection();

        // Register our DISTANCE function, that only pgSQL can understand
        if ($con->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $em->getConfiguration()->addCustomNumericFunction(
                'DISTANCE',
                'Give2Peer\Give2PeerBundle\Query\AST\Functions\DistanceFunction'
            );
        } else {
            return new JsonResponse(['error' => 'DB *must* be pgSQL.'], 500);
        }

        /** @var ItemRepository $repo */
        $repo = $em->getRepository('Give2PeerBundle:Item');

        $items = $repo->findByDistance($latitude, $longitude, 10000);

        return new JsonResponse($items);
    }



    //// UTILS /////////////////////////////////////////////////////////////////

    /**
     * Give an item whose properties are provided as POST variables.
     * Only the location property is mandatory.
     * There is no route acting on this directly, this is a helper.
     *
     * This creates an Item with the appropriate attributes, stores it and
     * sends it back as JSON.
     *
     * @param Request $request
     * @param bool $mine Is the item mine ?
     * @return JsonResponse
     */
    protected function giveOrSpotAction(Request $request, $mine)
    {
        // Recover the item data
        $location = $request->get('location');
        if (null == $location) {
            return new JsonResponse(["error"=>"No location provided."], 400);
        }
        $title = $request->get('title', '');
        $description = $request->get('description', '');

        // Recover the user data
        /** @var SecurityContext $sc */
        $sc = $this->get('security.context');
        $user = $sc->getToken()->getUser();

        // Create the item
        $item = new Item();
        $item->setLocation($location);
        try {
            $item->geolocate();
        } catch (\Exception $e) {
            $msg = sprintf("Cannot geolocate: %s", $e->getMessage());
            return new JsonResponse(["error"=>$msg], 400);
        }
        $item->setTitle($title);
        $item->setDescription($description);
        if ($mine) {
            $item->setGiver($user);
        } else {
            $item->setSpotter($user);
        }

        // Add the item to database
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        $em->persist($item);
        $em->flush();

        // Send the item as response
        return new JsonResponse($item);
    }



}
