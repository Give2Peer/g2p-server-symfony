<?php

namespace Give2Peer\Give2PeerBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * An action of report, by the reporter on the reportee, about an item.
 *
 * The (reporter,item) tuple should be unique as per the specs : no reporting twice.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Give2Peer\Give2PeerBundle\Entity\ReportRepository")
 */
class Report implements \JsonSerializable
{
    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        $json = [
            'id'             => $this->getId(),
            'created_at'     => $this->getCreatedAt()->format(DateTime::ISO8601),
            'reporter'       => $this->getReporter(),
            'reportee'       => $this->getReportee(),
            'item'           => $this->getItem(),
        ];

        return $json;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * The date and time (to the second) at which this report was made.
     *
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * The user that reported the other user for its item.
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="reportsMade")
     * (this JoinColumn is unnecessary as those are the default values)
     * @ORM\JoinColumn(name="reporter_id", referencedColumnName="id")
     */
    private $reporter;

    /**
     * The user that was reported for its item by the other user.
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="reportsReceived")
     * (this JoinColumn is unnecessary as those are the default values)
     * @ORM\JoinColumn(name="reportee_id", referencedColumnName="id")
     */
    private $reportee;

    /**
     * The (probably irrelevant) item that triggered this report.
     *
     * @var Item
     *
     * @ORM\ManyToOne(targetEntity="Item", inversedBy="reportsReceived")
     * (this JoinColumn is unnecessary as those are the default values)
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id")
     */
    private $item;


    ////////////////////////////////////////////////////////////////////////////

    //public function __construct() {}

    /**
     * Get the unique identifier of this thank action. Pretty useless.
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return User
     */
    public function getReporter()
    {
        return $this->reporter;
    }

    /**
     * @param User $reporter
     */
    public function setReporter($reporter)
    {
        $this->reporter = $reporter;
    }

    /**
     * @return User
     */
    public function getReportee()
    {
        return $this->reportee;
    }

    /**
     * @param User $reportee
     */
    public function setReportee($reportee)
    {
        $this->reportee = $reportee;
    }

    /**
     * @return Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param Item $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

}
