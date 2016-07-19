<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Gedmo\Sluggable\Util\Urlizer;
use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Give2Peer\Give2PeerBundle\Entity\Thank;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Give2Peer\Give2PeerBundle\Controller\ErrorCode as Error;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Give2Peer\Give2PeerBundle\Response\ExceededQuotaJsonResponse;
use Symfony\Component\Yaml\Yaml;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

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
     * You can only thank for a specific item once.
     *
     * You cannot thank yourself.
     *
     * #### Costs Karma
     *
     * This action costs 1 karma point to use. If you just levelled up, it's free ; it can't make you lose a level.
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
    public function thankForItemAction (Request $request, Item $item)
    {
        /** @var User $thanker */
        $thanker = $this->getUser();

        if (empty($thanker)) {
            return new ErrorJsonResponse("No thanker provided.", Error::NOT_AUTHORIZED);
        }

        $thankee = $item->getAuthor();

        if ($thanker == $thankee) {
            return new ErrorJsonResponse("You can't thank yourself.", Error::NOT_AUTHORIZED);
        }

        // Disallow thanking more than once for the same item
        $doneAlready = $this->getThankRepository()->findOneBy([
            'thanker' => $thanker,
            'item' => $item,
        ]);
        if ($doneAlready) {
            return new ErrorJsonResponse(
                "One thanks per item only.",
                Error::EXCEEDED_QUOTA
            );
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