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
     * Return all available tags sorted alphabetically.
     *
     * @ApiDoc()
     * @return JsonResponse
     */
    public function tagsAction()
    {
        $tags = $this->getTagRepository()->findBy([], ['name'=>'ASC']);

        return new JsonResponse([
            'tags' => $tags
        ]);
    }

    /**
     * Get statistics about the service.
     *
     * - users_count: number of registered users.
     * - items_count: number of published items right now.
     * - items_total: number of published items since the beginning.
     *
     * @ApiDoc()
     * @return JsonResponse
     */
    public function statsAction()
    {
        $usersCount = $this->getUserRepository()->countUsers();
        $itemsCount = $this->getItemRepository()->countItems();
        $itemsTotal = $this->getItemRepository()->totalItems();
        

        return new JsonResponse([
            'users_count' => $usersCount,
            'items_count' => $itemsCount,
            'items_total' => $itemsTotal,
        ]);
    }
}