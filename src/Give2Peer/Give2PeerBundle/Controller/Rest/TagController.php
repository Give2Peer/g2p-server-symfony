<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc; // used in annotations

/**
 * Actions about tags CRUD.
 */
class TagController extends BaseController
{
    /**
     * Return all available tags sorted alphabetically.
     *
     * @ApiDoc()
     * @return JsonResponse
     */
    public function indexAlphabeticallyAction()
    {
        $tags = $this->getTagRepository()->findBy([], ['name'=>'ASC']);

        return new JsonResponse([
            'tags' => $tags
        ]);
    }
}