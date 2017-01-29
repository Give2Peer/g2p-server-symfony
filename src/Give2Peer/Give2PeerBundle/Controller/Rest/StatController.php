<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;

use /** @noinspection PhpUnusedAliasInspection */
    Nelmio\ApiDocBundle\Annotation\ApiDoc; // /!\ used in annotations


/**
 * Get statistics about the service.
 */
class StatController extends BaseController
{
    /**
     * Get a compilation of all statistics about the service.
     *
     * - `users_count`: number of registered users.
     * - `items_count`: number of published items right now.
     * - `items_total`: number of published items since the beginning.
     *
     * @ApiDoc(
     *   section = "3. Others"
     * )
     * @return JsonResponse
     */
    public function allAction()
    {
        $usersCount = $this->getUserRepository()->countUsers();
        $itemsCount = $this->getItemRepository()->countItems();
        $itemsTotal = $this->getItemRepository()->totalItems();

        return $this->respond([
            'users_count' => $usersCount,
            'items_count' => $itemsCount,
            'items_total' => $itemsTotal,
        ]);
    }
}
