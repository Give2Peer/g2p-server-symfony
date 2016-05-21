<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Where we define our routinely run tasks.
 *
 * CRON handles launching daily from command-line `php cron/cron.php daily`
 * which in turn asks for the route `/v1/cron/daily` which asks this controller
 * `dailyAction`.
 * 
 * CRON configuration is available in `cron/`.
 * You need to symlink it to the right place with `script/setup_cron`.
 * 
 * Class CronController
 * @package Give2Peer\Give2PeerBundle\Controller
 */
class CronController extends BaseController
{

    /**
     * Delay in seconds after which soft-deleted items should be hard-deleted.
     * @var int
     */
    const ITEM_HARD_DELETION_DELAY = 60 * 60 * 24 * 7;

    /**
     * Default lifetime in seconds of items.
     * An item exceeding its lifetime will be soft-deleted.
     * @var int
     */
    const ITEM_DEFAULT_LIFETIME = 60 * 60 * 24 * 15;

    /**
     * Run the daily CRON task.
     *
     * - hard delete items that were soft deleted more than 7 days ago
     *   The minimum time that can be set between soft and hard deletion is
     *   24h because of the daily karma and daily quotas.
     * - soft delete non soft-deleted items whose last update is older than
     *   their lifespan.
     *
     */
    public function dailyAction()
    {
        $itemRepo = $this->getItemRepository();

        $content =
            "Give2Peer daily CRON task\n" .
            "-------------------------\n" .
            "\n";

        // soft delete old items exceeding their lifespan
        $sdc = $itemRepo->softDeleteOldItems(self::ITEM_DEFAULT_LIFETIME);
        $content .= "Soft deleted items : $sdc\n";

        // hard delete old soft-deleted items
        $hdc = $itemRepo->hardDeleteOldItems(self::ITEM_HARD_DELETION_DELAY);
        $content .= "Hard deleted items : $hdc\n";

        $response = new Response($content);

        return $response;
    }

}