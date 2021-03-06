<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Give2Peer\Give2PeerBundle\Entity\Report;
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
class ModerationController extends BaseController
{
    /**
     * Report the item `id` for abuse.
     *
     * #### Restrictions
     *
     *   - You need to be at least level 1.
     *   - You can only report abuse once per item.
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
     * 
     * @ApiDoc(
     *   section = "2. Items"
     * )
     *
     * @param  Request $request
     * @return ErrorJsonResponse|JsonResponse
     */
    public function reportItemAction (Request $request, $id)
    {
        $required_level = 1;
        // sum of reporters' karma must exceed the author's golden karma
        $defense_buff_factor = 1.618;

        $em = $this->getEntityManager();
        $rr = $this->getReportRepository();
        $user = $this->getUser();

        $cancel = $request->get("cancel", false);

        if ($cancel) {
            $item = $this->getItemIncludingDeleted($id);
        } else {
            $item = $this->getItem($id);
        }

        if (null == $item) {
            return $this->error("item.not_found", ['%id%' => $id]);
        }

        $thor = $item->getAuthor();

        if ($thor == $user) {
            return $this->error("item.report.own", ['%id%' => $id]);
        }

        if ($user->getLevel() < $required_level) {
            return $this->error("item.report.level_too_low", [
                '%id%'    => $id,
                '%level%' => $required_level,
            ]);
        }

        if ($cancel) {
            // We're canceling a previous report
            $report = $rr->findOneByUserAndItem($user, $item);
            if ( ! $report) {
                return $this->error("item.report.cancel", ['%id%' => $id]);
            }

            $em->remove($report);

        } else {
            // We're making a new report only if one does not exist yet
            if ($rr->hasUserReportedAlready($user, $item)) {
                return $this->error("item.report.twice", ['%id%' => $id]);
            }

            $report = new Report(); // such neat, very POPO     --   wow
            $report->setItem($item);
            $report->setReporter($user);
            $report->setReportee($thor);
            $em->persist($report);
        }

        $em->flush(); // important: flush before next line !
        $willAgainst = $rr->sumKarmicWillAgainstItem($item);

        $didWeDeleteSomething = false;
        if ($willAgainst > $thor->getKarma() * $defense_buff_factor) {
            $item->markAsDeleted(); // brutality
            $didWeDeleteSomething = true;
        } else {
            $item->unmarkAsDeleted();
        }

        $em->flush();

        return $this->respond([
            'item'         => $item,
            'item_deleted' => $didWeDeleteSomething,
            // possible author_deleted boolean flag in the future
        ]);
    }
}