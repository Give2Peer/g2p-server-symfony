<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Gedmo\Sluggable\Util\Urlizer;
use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Give2Peer\Give2PeerBundle\Entity\Report;
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
class ModerationController extends BaseController
{
    /**
     * Report the item `id` for abuse.
     *
     * #### Restrictions
     *
     *   - You need to be at least level 1.
     *   - You can only report abuse for a specific item once.
     *   - You cannot report for abuse items you authored yourself.
     *
     * #### Effects
     *
     * When the sum of the reporters' karma exceed the item author's golden karma, the item is soft-deleted.
     *
     * #### Possibly Costs Karma
     *
     * This may cost karma points to use. _(it does not, right now)_
     *
     * #### Features
     *
     *   - [reporting_abuse.feature](https://github.com/Give2Peer/g2p-server-symfony/blob/master/features/reporting_abuse.feature)
     *
     * @fixme: _make reporting cost karma points_ ?
     * 
     * @ApiDoc()
     *
     * @param  Request $request
     * @return ErrorJsonResponse|JsonResponse
     */
    public function reportItemAction (Request $request, $id)
    {
        // sum of reporters' karma must exceed buffed up author's karma
        $defense_buff_factor = 1.618;

        $em = $this->getEntityManager();
        $rr = $this->getReportRepository();
        $user = $this->getUser();
        $item = $this->getItem($id);

        if (null == $item) {
            return new ErrorJsonResponse(
                "Item #$id does not exist.", Error::NOT_AUTHORIZED
            );
        }

        $thor = $item->getAuthor();

        if ($thor == $user) {
            return new ErrorJsonResponse(
                "Can't report your own item #$id.", Error::NOT_AUTHORIZED
            );
        }

        if ($user->getLevel() < 1) {
            return new ErrorJsonResponse(
                "Level too low to report item #$id.", Error::NOT_AUTHORIZED
            );
        }

        if ($rr->hasUserReportedAlready($user, $item)) {
            return new ErrorJsonResponse(
                "Can't report twice item #$id.", Error::NOT_AUTHORIZED
            );
        }

        $thank = new Report(); // such neat, very POPO        wow
        $thank->setItem($item);
        $thank->setReporter($user);
        $thank->setReportee($thor);
        $em->persist($thank);
        $em->flush();

        $willAgainst = $rr->sumKarmicWillAgainstItem($item);

        if ($willAgainst > $thor->getKarma() * $defense_buff_factor) {
            $item->markAsDeleted(); // brutality
        }

        $em->flush();

        return $this->respond([
            'item' => $item
        ]);
    }
}