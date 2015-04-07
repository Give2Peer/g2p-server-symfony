<?php

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\Tag;
use Give2Peer\Give2PeerBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Behat\Behat\Context\Context as BehatContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DomCrawler\Crawler;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Faker\Factory as FakerFactory;
use Faker\Generator;

/**
 * Returns whatever is in $array1 but not in $array2.
 * Could be optimized, if it mattered :3
 *
 * @param $array1
 * @param $array2
 * @return array
 */
function array_diff_assoc_recursive($array1, $array2) {
    $diff = array();
    foreach ($array1 as $k => $v) {
        if (!isset($array2[$k])) {
            $diff[$k] = $v;
        }
        else if (!is_array($v) && is_array($array2[$k])) {
            $diff[$k] = $v;
        }
        else if (is_array($v) && !is_array($array2[$k])) {
            $diff[$k] = $v;
        }
        else if (is_array($v) && is_array($array2[$k])) {
            $array3 = array_diff_assoc_recursive($v, $array2[$k]);
            if (!empty($array3)) $diff[$k] = $array3;
        }
        else if ((string)$v != (string)$array2[$k]) {
            $diff[$k] = $v;
        }
    }
    return $diff;
}

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

    /** @var Generator $faker */
    protected $faker;

    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }

    /**
     * Prepare system for test suite before it runs,
     * by booting the kernel (in test mode, apparently)
     * and loading fresh fixtures into an empty db.
     * @BeforeScenario
     */
    public function prepare(BeforeScenarioScope $scope)
    {
        // Boot the kernel
        static::bootKernel();

        // Add all the fixtures classes that implement
        // Doctrine\Common\DataFixtures\FixtureInterface
//        $this->loadFixtures(array(
//            'Give2Peer\Give2PeerBundle\DataFixtures\ORM\LoadData',
//        ));
        // Loading an empty array still truncates all tables.
        $this->loadFixtures(array());

        // Empty the public directory where pictures are
        // THIS IS DANGEROUS !
        // It means that this test suite can never EVER be run on the prod server
        // This is BAD.
    }

    /**
     * @AfterSuite
     */
    public static function gimmeCookieNomNomNom(AfterSuiteScope $scope)
    {
        // Let's make a meme : a fortune cookie each time the suite runs okay
        if ($scope->getTestResult()->isPassed()) {
            try { print(shell_exec('fortune -a')); } catch (\Exception $e) {}
        }
    }


    // TRANSFORMERS ////////////////////////////////////////////////////////////

    /**
     * A very handy transformer, registered to Behat.
     * @Transform /^(-?\d+)$/
     */
    public function castStringToInt($string)
    {
        return intval($string);
    }

    /**
     * A very handy transformer, registered to Behat.
     * @Transform /^(-?\d+\.\d*)$/
     */
    public function castStringToFloat($string)
    {
        return floatval($string);
    }


    // DUMMY STEPS /////////////////////////////////////////////////////////////


    /**
     * @Given I do nothing
     */
    public function iDoNothing() {}

    /**
     * @Then nothing happens
     */
    public function nothingHappens() {}

    /**
     * @Given I print :arg1
     */
    public function iPrint($arg1)
    {
        print($arg1);
    }

    /**
     * @Then /^I (?:print|dump) the response$/
     */
    public function iPrintTheResponse()
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        print($this->client->getResponse()->getContent());
    }


    // FIXTURES STEPS //////////////////////////////////////////////////////////


    /**
     * @Given /^I am the registered user named "(.*)" *$/
     */
    public function iAmTheRegisteredUserNamed($name)
    {
        $um = $this->getUserManager();
        $user = $um->findUserByUsername($name);

        if (empty($user)) {
            $user = $this->createUser($name);
        }

        $this->user = $user;
    }

    /**
     * @Given /^there is a user named "(.*)" *$/
     */
    public function thereIsAUserNamed($name)
    {
        $this->createUser($name);
    }

    /**
     * @Given /^there is a tag named "(\w+)" *$/
     */
    public function thereIsATagNamed($name)
    {
        // Create the tag
        $tag = new Tag();
        $tag->setName($name);

        // Add the tag to database
        $em = $this->getEntityManager();
        $em->persist($tag);
        $em->flush();
    }

    /**
     * @Given /^there is an item at (-?\d+\.\d*) ?, ?(-?\d+\.\d*)$/
     */
    public function thereIsAnItemAt($latitude, $longitude)
    {
        // Create the item
        $item = new Item();
        $item->setTitle(sprintf("%s %s",
            $this->faker->colorName, $this->faker->word));
        $item->setLocation("$latitude, $longitude");
        $item->setLatitude($latitude);
        $item->setLongitude($longitude);

        // Add the item to database
        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $em->persist($item);
        $em->flush();
    }

    /**
     * @Given /^there are (\d+) items at (-?\d+\.\d*) ?, ?(-?\d+\.\d*)$/
     */
    public function thereAreItemsAt($howMany, $latitude, $longitude)
    {
        for ($i=0; $i<$howMany; $i++) {
            $this->thereIsAnItemAt($latitude, $longitude);
        }
    }


    // REQUEST STEPS ///////////////////////////////////////////////////////////


    /**
     * @When /^I GET ([^ ]+)$/
     */
    public function iGet($route)
    {
        $this->request('GET', $route);
    }

    /**
     * @When /^I POST to ([^ ]+) the following ?:$/
     */
    public function iPost($route, $pystring='')
    {
        $data = $this->fromYaml($pystring);
        $this->request('POST', $route, $data);
    }

    /**
     * @When /^I POST to ([^ ]+) the file (.+)?$/
     */
    public function iPostTheFile($route, $filePath)
    {
        // We need to make a copy of the file, 'cause it will be *moved*
        $sinfo = new \SplFileInfo($filePath);
        $extension = $sinfo->getExtension();
        $tmpFilePath = $sinfo->getBasename('.'.$extension).'_copy.'.$extension;

        $finfo = new finfo;
        $mime = $finfo->file($sinfo->getRealPath(), FILEINFO_MIME);

        copy($filePath, $tmpFilePath);

        $picture = new UploadedFile(
            $tmpFilePath,
            $sinfo->getFilename(),
            $mime,
            filesize($filePath),
            UPLOAD_ERR_OK,
            true // test mode ?
        );
        $files = ['picture' => $picture];

        $this->request('POST', $route, [], $files);
    }


    // RESPONSE STEPS //////////////////////////////////////////////////////////


    /**
     * @Then /^the request should (not )?be accepted$/
     */
    public function theRequestShouldBeAcceptedOrNot($not = '')
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        $content = $this->client->getResponse()->getContent();
        // Good try, but it floods the console too much :(
        //try {
        //    $content = json_encode(json_decode($content), JSON_PRETTY_PRINT);
        //} catch (\Exception $e) {}

        if ($this->client->getResponse()->isSuccessful() && !empty($not)) {
            $this->fail(
                sprintf("Response is successful, with '%d' return code " .
                    "and the following content:\n%s",
                    $this->client->getResponse()->getStatusCode(),
                    $content));
        }

        if (!$this->client->getResponse()->isSuccessful() && empty($not)) {
            $this->fail(
                sprintf("Response is unsuccessful, with '%d' return code " .
                    "and the following content:\n%s",
                    $this->client->getResponse()->getStatusCode(),
                    $content));
        }
    }

    /**
     * Provide YAML in the pystring, it will be arrayed and compared with the
     * other array in the response's data.
     * @Then /^the response should((?: not)?) include ?:$/
     */
    public function theResponseShouldInclude($not='', $pystring='')
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        $expected = $this->fromYaml($pystring);

        $response = $this->client->getResponse();
        $actual = json_decode($response->getContent(), true);

        $missing = array_diff_assoc_recursive($expected, $actual);
        $notMissing = array_diff_assoc_recursive($expected, $missing);

        if (empty($not) && !empty($missing)) {
            $this->fail(sprintf(
                "The response did not include the following:\n%s\n" .
                "Because the response provided:\n%s",
                print_r($missing, true),
                print_r($actual, true)
            ));
        }

        if (!empty($not) && !empty($notMissing)) {
            $this->fail(sprintf(
                "The response did include the following:\n%s\n" .
                "Because the response provided:\n%s",
                print_r($notMissing, true),
                print_r($actual, true)
            ));
        }
    }

    /**
     * @Then /^there should be (\d+) items? in the response$/
     */
    public function thereShouldBeItemsInTheResponse($howMany)
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        $response = $this->client->getResponse();
        $actual = (array) json_decode($response->getContent());

        if (count($actual) != $howMany) {
            $this->fail(sprintf(
                "The response sent %d item(s) back,\n" .
                "Because the response provided:\n%s",
                print_r(count($actual), true),
                print_r($actual, true)
            ));
        }
    }


    // CHECKS STEPS ////////////////////////////////////////////////////////////


    /**
     * @Then /^there should be (\d+) (item|tag|user)s? in the database$/
     */
    public function thereShouldBeItemInTheDatabase($thatMuch, $what)
    {
        $em = $this->getEntityManager();
        $count = $em->createQueryBuilder()
            ->select('COUNT(e)')
            ->from(sprintf('Give2PeerBundle:%s', ucfirst($what)), 'e')
            ->getQuery()
            ->execute()
            [0][1]
            ;

        $this->assertEquals($thatMuch, $count);
    }


    /**
     * @Then /^there should((?: not)?) be a file at (.*?) *$/
     */
    public function thereShouldBeAFileAt($not, $path)
    {
        $not = ($not == '') ? false : true;
        // If not absolute, assume relative to parent of kernel dir
        if (strpos($path, DIRECTORY_SEPARATOR) !== 0) {
            $prepend = $this->get('kernel')->getRootDir().'/../';
            // you can use getcwd() if the above causes you trouble
            //$prepend = getcwd();
            $path = $prepend . DIRECTORY_SEPARATOR . $path;
        }

        $thereIsFile = is_file($path);
        if ($not && $thereIsFile) {
            $this->fail("File found at ${path}");
        }
        if (!$not && !$thereIsFile) {
            $this->fail("No file found at ${path}");
        }
    }


    // UTILS ///////////////////////////////////////////////////////////////////


    /**
     * Create a dummy user named $name with password $name
     *
     * @param $name
     * @return User
     */
    protected function createUser($name)
    {
        $um = $this->getUserManager();

        $user = $um->createUser();
        $user->setEmail('peer@give2peer.org');
        $user->setUsername($name);
        $user->setPlainPassword($name);
        $user->setEnabled(true);

        // This will canonicalize, encode, persist and flush
        $um->updateUser($user);

        return $user;
    }

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

    /**
     * Like Client's request, but with our contextual HTTP auth in the headers.
     *
     * @param $method
     * @param $uri
     * @param array $parameters
     * @param array $files
     * @param array $server
     * @param null $content
     * @param bool $changeHistory
     * @return Crawler
     */
    protected function request($method, $uri, array $parameters = array(),
                               array $files = array(), array $server = array(),
                               $content = null, $changeHistory = true)
    {
        $client = $this->getOrCreateClient();

        if (!empty($this->user)) {
            $server['PHP_AUTH_USER'] = $this->user->getUsername();
            $server['PHP_AUTH_PW']   = $this->user->getUsername();
        }

        $this->crawler = $client->request(
            $method, $uri, $parameters, $files,
            $server, $content, $changeHistory
        );

        return $this->crawler;
    }
}