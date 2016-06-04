<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc; // used

/**
 * Get statistics about the service.
 */
class StatController extends BaseController
{
    /**
     * Get a compilation of all statistics about the service.
     *
     * - users_count: number of registered users.
     * - items_count: number of published items right now.
     * - items_total: number of published items since the beginning.
     *
     * @ApiDoc()
     * @return JsonResponse
     */
    public function allAction()
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
