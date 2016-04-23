<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc; // used

/**
 * Contains only GET routes that do not need an authenticated user to work.
 * (later) Errr... I'm pretty sure this is behind a firewall.
 * 
 * Let's say this is for routes that do not deserve their own controller.
 */
class DataController extends BaseController
{
    /**
     * Return all available tags, as a JSONed array.
     *
     * @ApiDoc()
     * @return JsonResponse
     */
    public function tagsAction()
    {
        $tags = $this->getTagRepository()->getTagNames();

        return new JsonResponse($tags);
    }

    /**
     * Get statistics about the service.
     *
     * @ApiDoc()
     * @return JsonResponse
     */
    public function statsAction()
    {
        // Number of registered users
        $usersCount = $this->getUserRepository()->countUsers();
        // Number of published items
        $itemsCount = $this->getItemRepository()->countItems();

        return new JsonResponse([
            'users_count' => $usersCount,
            'items_count' => $itemsCount,
        ]);
    }
}