<?php

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpKernel\KernelInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
//use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Behat\Behat\Context\Context as BehatContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DomCrawler\Crawler;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;


class FeatureContext
    extends WebTestCase
    implements BehatContext, SnippetAcceptingContext {

    /** @var Client $client */
    protected $client;

    /** @var Crawler $crawler */
    protected $crawler;

    protected function get($id) {
        return static::$kernel->getContainer($id);
    }

    /**
     * Prepare system for test suite before it runs
     * @BeforeScenario
     */
    public function prepare(BeforeScenarioScope $scope)
    {
        static::bootKernel();

        // Add all the fixtures classes that implement
        // Doctrine\Common\DataFixtures\FixtureInterface
        $this->loadFixtures(array());
//        $this->loadFixtures(array(
//            'Give2Peer\Give2PeerBundle\DataFixtures\ORM\LoadData',
//        ));

    }

    /**
     * @AfterSuite
     */
    public static function afterTheSuite(AfterSuiteScope $scope)
    {
        // Let's make a meme : a fortune cookie each time the suite runs okay
        // not sure how I get the ScenarioTest status out of Scope though.
        if ($scope->getTestResult()->isPassed()) {
            try {
                print(shell_exec('fortune'));
            } catch (\Exception $e) {}
        }
    }

    /**
     * A very handy transformer, registered to Behat.
     * @Transform /^(-?\d+)$/
     */
    public function castStringToNumber($string)
    {
        return intval($string);
    }

    /**
     * @When /^I POST to ([^ ]+) the following ?:$/
     */
    public function iPost($route, $pystring='')
    {
        $client = $this->getOrCreateClient();
        $data = $this->fromYaml($pystring);
        $this->crawler = $client->request('POST', $route, $data);
    }

    /**
     * @Then /^the request should (not )?be accepted$/
     */
    public function theRequestShouldBeAccepted($not = '')
    {
        if (empty($this->client)) {
            throw new Exception("Inexistent client. Request something first.");
        }

        if ($this->client->getResponse()->isSuccessful() && !empty($not)) {
            $this->fail(
                sprintf("Response is successful, with '%d' return code " .
                    "and the following content:\n%s",
                    $this->client->getResponse()->getStatusCode(),
                    $this->client->getResponse()->getContent()));
        }

        if (!$this->client->getResponse()->isSuccessful() && empty($not)) {
            $this->fail(
                sprintf("Response is unsuccessful, with '%d' return code " .
                    "and the following content:\n%s",
                    $this->client->getResponse()->getStatusCode(),
                    $this->client->getResponse()->getContent()));
        }
    }

    /**
     * @Then /^the response should include ?:$/
     */
    public function theResponseShouldInclude($route, $pystring='')
    {
        if (empty($this->client)) {
            throw new Exception("Inexistent client. Request something first.");
        }

        $data = (array) $this->fromYaml($pystring);

        $res = $this->client->getResponse();

        $actual = (array) json_decode($res->getContent());

        $intersect = array_intersect_assoc($data, $actual);
        if (count($data) > count($intersect)) {
            $notfound = array_diff_assoc($intersect, $data);
            $this->fail(sprintf(
                "The response did not include the following:\n%s",
                print_r($notfound, true)
            ));
        }

    }

    /**
     * @Then /^there should be (\d+) items? in the database$/
     */
    public function thereShouldBeItemInTheDatabase($thatMuch)
    {
        $container = static::$kernel->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $count = $em->createQuery(
            'SELECT COUNT(i) FROM \Give2Peer\Give2PeerBundle\Entity\Item i'
        )->getResult();

        $this->assertEquals($thatMuch, $count[0][1]);
    }

    ////////////////////////////////////////////////////////////////////////////

    protected function getOrCreateClient(array $options = array(),
                                         array $server = array()) {

        if (empty($this->client)) {
            $this->client = $this->createClient($options, $server);
        }
        return $this->client;
    }

    protected function fromYaml($pystring) {
        return Yaml::parse($pystring);
    }


//    protected $kernel;
//
//    /**
//     * Sets Kernel instance.
//     *
//     * @param KernelInterface $kernel
//     */
//    public function setKernel(KernelInterface $kernel)
//    {
//        $this->$kernel = $kernel;
//    }
}