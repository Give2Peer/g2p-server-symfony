<?php

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpKernel\KernelInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Behat\Behat\Context\Context as BehatContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DomCrawler\Crawler;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

/**
 * “You will not censor me through bug terrorism.”
 *     -- James Troup
 *
 * Class FeatureContext
 */
class FeatureContext
    extends BaseContext
    implements BehatContext, SnippetAcceptingContext {

    /** @var Client $client */
    protected $client;

    /** @var Crawler $crawler */
    protected $crawler;

    /** @var User $user */
    protected $user;

    /**
     * Prepare system for test suite before it runs,
     * by booting the kernel (in test mode, apparently)
     * and loading fresh fixtures into an empty db.
     * @BeforeScenario
     */
    public function prepare(BeforeScenarioScope $scope)
    {
        static::bootKernel();

        // Add all the fixtures classes that implement
        // Doctrine\Common\DataFixtures\FixtureInterface
//        $this->loadFixtures(array(
//            'Give2Peer\Give2PeerBundle\DataFixtures\ORM\LoadData',
//        ));
        // Loading an empty array still truncates all tables.
        $this->loadFixtures(array());

    }

    /**
     * @AfterSuite
     */
    public static function afterTheSuite(AfterSuiteScope $scope)
    {
        // Let's make a meme : a fortune cookie each time the suite runs okay
        if ($scope->getTestResult()->isPassed()) {
            try { print(shell_exec('fortune')); } catch (\Exception $e) {}
        }
    }


    // STEPS ///////////////////////////////////////////////////////////////////

    /**
     * A very handy transformer, registered to Behat.
     * @Transform /^(-?\d+)$/
     */
    public function castStringToNumber($string)
    {
        return intval($string);
    }

    /**
     * @Given /^I am the registered user named (.*) *$/
     */
    public function iAmTheRegisteredUserNamed($name)
    {
        /** @var \FOS\UserBundle\Entity\UserManager $um */
        $um = $this->get('fos_user.user_manager');
        $user = $um->findUserByUsername($name);

        if (empty($user)) {
            $user = $um->createUser();
            $user->setEmail('peer@give2peer.org');
            $user->setUsername($name);
            $user->setPlainPassword($name);
            $user->setEnabled(true);

            // This will canonicalize, encode, persist and flush
            $um->updateUser($user);
        }

        $this->user = $user;
    }

    /**
     * @When /^I POST to ([^ ]+) the following ?:$/
     */
    public function iPost($route, $pystring='')
    {
        $client = $this->getOrCreateClient();
        $data = $this->fromYaml($pystring);
        $headers = array();
        if (!empty($this->user)) {
            $headers['PHP_AUTH_USER'] = $this->user->getUsername();
            $headers['PHP_AUTH_PW']   = $this->user->getUsername();
        }
        $this->crawler = $client->request('POST', $route, $data, [], $headers);
    }

    /**
     * @Then /^the request should (not )?be accepted$/
     */
    public function theRequestShouldBeAcceptedOrNot($not = '')
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
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
     * Provide YAML in the pystring, it will be arrayed and compared with the
     * other array in the response's data.
     * @Then /^the response should include ?:$/
     */
    public function theResponseShouldInclude($pystring='')
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        $expected = $this->fromYaml($pystring);

        $response = $this->client->getResponse();
        $actual = (array) json_decode($response->getContent());

        $intersect = array_intersect_assoc($expected, $actual);
        if (count($expected) > count($intersect)) {
            $notfound = array_diff_assoc($expected, $intersect);
            $this->fail(sprintf(
                "The response did not include the following:\n%s\n" .
                "Because the response provided:\n%s",
                print_r($notfound, true),
                print_r($actual, true)
            ));
        }

    }

    /**
     * @Then /^there should be (\d+) items? in the database$/
     */
    public function thereShouldBeItemInTheDatabase($thatMuch)
    {
        $em = $this->getEntityManager();
        $count = $em->createQuery(
            'SELECT COUNT(i) FROM Give2Peer\Give2PeerBundle\Entity\Item i'
        )->getResult();

        $this->assertEquals($thatMuch, $count[0][1]);
    }


    // UTILS ///////////////////////////////////////////////////////////////////


    /**
     * @param array $options
     * @param array $server
     * @return Symfony\Bundle\FrameworkBundle\Client
     */
    protected function getOrCreateClient(array $options = array(),
                                         array $server = array()) {
        if (empty($this->client)) {
            $this->client = $this->createClient($options, $server);
        }
        return $this->client;
    }
}