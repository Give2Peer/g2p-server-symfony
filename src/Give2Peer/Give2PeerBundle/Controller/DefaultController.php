<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('Give2PeerBundle:Default:index.html.twig');
    }

    public function giveAction(Request $request)
    {
        // Recover the item data
        $location = $request->get('location');
        if (null == $location) {
            return new JsonResponse(["error"=>"No location provided."], 400);
        }

        // Recover the user data -- todo

        // Create the item
        $item = new Item();
        $item->setLocation($location);

        // Add the item to database
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        $em->persist($item);
        $em->flush();

        // Send the item as response
        return new JsonResponse($item);
    }



}
