<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\Item;
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
     * A Giver is the owner of the item.
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
     * @param Request $request
     * @return JsonResponse
     */
    public function spotAction(Request $request)
    {
        return $this->giveOrSpotAction($request, false);
    }

    /**
     * Give an item whose properties are provided as POST variables.
     * Only the location property is mandatory.
     * There is no route acting on this directly, this is a helper.
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
