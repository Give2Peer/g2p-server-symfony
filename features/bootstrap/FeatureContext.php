<?php

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
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

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;


class FeatureContext
    extends WebTestCase
    implements BehatContext, SnippetAcceptingContext {

    /** @var Client $client */
    protected $client;

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

        // add all your fixtures classes that implement
        // Doctrine\Common\DataFixtures\FixtureInterface
        $this->loadFixtures(array());
//        $this->loadFixtures(array(
//            'Give2Peer\Give2PeerBundle\DataFixtures\ORM\LoadData',
//        ));

//        $doctrine = new sfDoctrineDropDbTask($configuration->getEventDispatcher(), new sfAnsiColorFormatter());
//        $doctrine->run(array(), array("--no-confirmation","--env=test"));
//
//        $doctrine = new sfDoctrineBuildDbTask($configuration->getEventDispatcher(), new sfAnsiColorFormatter());
//        $doctrine->run(array(), array("--env=test"));
//
//        $doctrine = new sfDoctrineInsertSqlTask($configuration->getEventDispatcher(), new sfAnsiColorFormatter());
//        $doctrine->run(array(), array("--env=test"));

        /** @var Connection $connection */
//        $connection = static::$kernel->getContainer()->get('doctrine.dbal.default_connection');
//        $sm = $connection->getSchemaManager();
//
//        $sm->dropAndCreateDatabase();

//        $connection->executeQuery(sprintf('SET FOREIGN_KEY_CHECKS = 0;'));
//        foreach ($sm->listTableNames() as $tableName) {
//            $connection->executeQuery(sprintf('TRUNCATE TABLE %s', $tableName));
//        }
//        $connection->executeQuery(sprintf('SET FOREIGN_KEY_CHECKS = 1;'));
    }

    /**
     * @AfterScenario
     */
    public function cleanDB(AfterScenarioScope $scope)
    {
        // clean database after scenarios,
        // tagged with @database
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
        $crawler = $client->request('POST', $route, $data);
    }

    /**
     * @Then /^the request should be accepted$/
     */
    public function theRequestShouldBeAccepted()
    {
        if (empty($this->client)) {
            throw new Exception("Inexistent client. Request something first.");
        }

        $this->assertTrue($this->client->getResponse()->isSuccessful(),
            sprintf("Response is unsuccessful, with '%d' return code.",
                $this->client->getResponse()->getStatusCode()));
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