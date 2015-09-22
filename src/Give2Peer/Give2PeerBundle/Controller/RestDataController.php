<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Give2Peer\Give2PeerBundle\Controller\ErrorCode as Error;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Give2Peer\Give2PeerBundle\Response\ExceededQuotaJsonResponse;

/**
 * Contains only GET routes that do not need an authenticated user to work.
 */
class RestDataController extends BaseController
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