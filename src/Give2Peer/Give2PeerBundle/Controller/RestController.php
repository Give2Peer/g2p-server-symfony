<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Routes are configured in YAML, in `Resources/config/routing.yml`.
 */
class RestController extends BaseController
{

    /** Number of item queries per day a user may make at level 0 */
    const ITEM_QUERIES_LEVEL_0 = 30;
    /** Number of item queries per level and per day a user may make */
    const ITEM_QUERIES_PER_LEVEL = 15;

    /**
     * Display a generated documentation and interactive sandbox about this API.
     * NelmioApiDoc's documentation can be found at :
     * https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.md
     */
    public function indexAction()
    {
        return $this->forward('NelmioApiDocBundle:ApiDoc:index');
    }

    /**
     * Check your connectivity to the server.
     *
     * For clients that want to check their connectivity to the server.
     *
     * The response of this should be unmistakable of a server supporting the
     * give2peer ~protocol, and should provide some public information about the
     * server and API and maybe even some very basic stats, or metadata.
     *
     * This action is not protected by the firewall and therefore does not
     * require credentials. This should be the only API action that is so.
     *
     * fixme: This should return useful information about the server.
     *
     * ... then we'll be able to enable
     * ApiDoc()
     *
     * @return JsonResponse
     */
    public function helloAction()
    {
        return $this->respond("pong");
    }

    /**
     * Check your credentials on the server.
     *
     * For clients that want to check both their connectivity and credentials
     * with the server.
     *
     * The response of this should be unmistakable of a server supporting the
     * give2peer ~protocol, and should provide some public information about the
     * server and API and maybe even some very basic stats, or metadata.
     *
     * fixme: This should return useful information about the server.
     *
     * ... then we'll be able to enable
     * ApiDoc()
     *
     * @return JsonResponse
     */
    public function checkAction()
    {
        return $this->respond("pong");
    }

}
