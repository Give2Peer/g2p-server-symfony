<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Reports repository.
 * Not much to say...
 */
class ReportRepository extends EntityRepository
{
    /**
     * @param $user
     * @param $item
     * @return Report|object
     */
    public function findOneByUserAndItem($user, $item) {
        $report = $this->findOneBy([
            'reporter' => $user,
            'item'     => $item,
        ]);

        return $report;
    }

    /**
     * Not overly optimized, but works.
     *
     * @param $user
     * @param $item
     * @return bool Whether or not the $user has reported this $item or not already.
     */
    public function hasUserReportedAlready($user, $item) {
        return (bool) $this->findOneByUserAndItem($user, $item);
    }

    /**
     * Not optimized either. You know what they say about premature optimization...
     *
     * @param $item
     * @return int The overall (summed up) karmic will against the $item.
     */
    public function sumKarmicWillAgainstItem($item) {
        $karmicWill = 0;
        $reportsCollection = $this->findBy(['item' => $item]);
        foreach ($reportsCollection as $k => $report) {
            $karmicWill += $report->getReporter()->getKarma();
        }
        return $karmicWill;
    }
}
