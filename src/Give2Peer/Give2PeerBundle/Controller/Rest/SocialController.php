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
     * This will cost karma points to use. _(it does not, right now)_
     *
     * #### Features
     *
     *   - [thanking_someone.feature](https://github.com/Give2Peer/g2p-server-symfony/blob/master/features/thanking_someone.feature)
     *
     * @fixme: make thanking cost karma points
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
            return new ErrorJsonResponse("Nope.", Error::NOT_AUTHORIZED);
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

        // GAME DESIGN
        // The idea is to give karma to make karma.
        // Thanker should choose how much karma he gives,
        // and it should be multiplied by a coefficient
        // that depends on its level, and thankee should receive it all.
        // But if you just levelled up you won't lose any karma so you can't
        // lose your level.
        // Let's say for now the coefficient is the level, and users don't lose
        // karma. They will. Maybe even before release.
        $karma_given = 0;
        $karma_received = $thanker->getLevel() + 1;
        ///

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
        
        return new JsonResponse([
            'thank'  => $thank,
        ]);
    }
    
}