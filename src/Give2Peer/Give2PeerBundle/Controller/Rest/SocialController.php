<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Give2Peer\Give2PeerBundle\Entity\Thank;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use /** @noinspection PhpUnusedAliasInspection */
    Nelmio\ApiDocBundle\Annotation\ApiDoc; // /!\ used in annotations

/**
 * Routes are configured in YAML, in `Resources/config/routing.yml`.
 * ApiDoc's documentation can be found at :
 * https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.md
 */
class SocialController extends BaseController
{

    /**
     * Thank the author of the Item `id`.
     *
     * You can only thank for a specific item once,
     * and you cannot thank yourself.
     *
     * #### Costs Karma
     *
     * This action costs 1 karma point to use.
     * If you just levelled up, it's free ; thanking cannot make you lose a level.
     *
     * #### Effects
     *
     * The author of the item will receive as much karma as your karmic level plus one.
     *
     * #### Features
     *
     *   - [thanking_someone.feature](https://github.com/Give2Peer/g2p-server-symfony/blob/master/features/thanking_someone.feature)
     *
     * @ApiDoc()
     *
     * @param  Request $request
     * @return ErrorJsonResponse|JsonResponse
     */
    public function thankForItemAction (Request $request, $id)
    {
        /** @var User $thanker */
        $thanker = $this->getUser();

        if (empty($thanker)) {
            return $this->error("item.thank.no_thanker");
        }

        $item = $this->getItem($id);

        if (null == $item) {
            return $this->error("item.not_found", ['%id%' => $id]);
        }

        $thankee = $item->getAuthor();

        if ($thanker == $thankee) {
            return $this->error("item.thank.yourself");
        }

        // Disallow thanking more than once for the same item
        if ($this->getThankRepository()->hasUserThankedAlready($thanker, $item)) {
            return $this->error("item.thank.twice");
        }

        $karma_given = min(1, $thanker->getKarmaProgress());
        $karma_received = $thanker->getLevel() + 1;

        $thanker->addKarma(-1 * $karma_given);
        $thankee->addKarma($karma_received);

        $thank = new Thank();
        $thank->setItem($item);
        $thank->setThanker($thanker);
        $thank->setThankee($item->getAuthor());
        $thank->setKarmaReceived($karma_received);
        $thank->setKarmaGiven($karma_given);
        
        $em = $this->getEntityManager();
        $em->persist($thank);
        $em->flush();
        
        return $this->respond([
            'thank'  => $thank,
        ]);
    }
    
}