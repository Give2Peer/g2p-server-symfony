<?php

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
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
 * Can be optimized, if it matters to you :3
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
 * This prints a fortune cookie when it passes ; sugar for the mind.
 */
class FeatureContext extends BaseContext
                     implements BehatContext, SnippetAcceptingContext
{

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
        $this->faker->addProvider(new GeolocationFaker($this->faker));
    }

    /**
     * Prepare system for test suite before it runs,
     * by booting the kernel (in test mode, apparently)
     * and loading fresh fixtures into an empty db.
     *
     * This is run before each new Scenario.
     *
     * @BeforeScenario
     */
    public function prepare(BeforeScenarioScope $scope)
    {
        // (Re)Boot the kernel
        static::bootKernel();

        // Empty the database by TRUNCATING the tables and RESETTING the indices
        // This is more complicated than it should, because of pgSQL
        $tables = [
            'Peer', // User is named Peer in the database, as User is reserved
            'Item',
            'Tag',
        ];
        // wip: Try to get the above list procedurally to avoid maintaining it ?
        // 1. Nope, TMI
        //$tables = $doc->query('SELECT * FROM pg_catalog.pg_tables')->fetchAll();

        /** @var Connection $dbal */
        $dbal = $this->get('doctrine.dbal.default_connection');
        foreach ($tables as $table) {
            $dbal->query("TRUNCATE TABLE ${table} RESTART IDENTITY CASCADE")
                 ->execute()
                 ;
            // WOW !! RESTART IDENTITY does not work, don't know why ?!
            // ... well, we reset the primary keys by hand, that works
            $dbal->query("ALTER SEQUENCE ${table}_id_seq RESTART WITH 1")
                 ->execute()
                 ;
        }

        // Loading an empty array still truncates all tables.
        $this->loadFixtures(array());

        // Empty the public directory where pictures are
        // THIS IS DANGEROUS !
        // It means that this test suite can never EVER be run on the prod server
        // This is BAD.
        // So, no. I'll move some things to configuration, and we'll try again.
        // Meanwhile, just delete by hand the files created in web/pictures
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
     * A very handy transformer for integers, registered to Behat.
     * @Transform /^(-?\d+)$/
     */
    public function castStringToInt($string)
    {
        return intval($string);
    }

    /**
     * A very handy transformer for floats, registered to Behat.
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
     * Useful for quick'n dirty debugging.
     * @Then /^I (?:print|dump) the response$/
     */
    public function iDumpTheResponse()
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }
        $content = $this->client->getResponse()->getContent();
        try {
            $content = json_encode(json_decode($content), JSON_PRETTY_PRINT);
        } catch (\Exception $e) {}

        print($content."\n");
    }


    // FIXTURES STEPS //////////////////////////////////////////////////////////

    /**
     * @Given I load the fixtures
     */
    public function iLoadTheFixtures()
    {
        // Add all the fixtures classes that implement
        // Doctrine\Common\DataFixtures\FixtureInterface
        $this->loadFixtures(array(
            'Give2Peer\Give2PeerBundle\DataFixtures\ORM\LoadTagsData',
            'Give2Peer\Give2PeerBundle\DataFixtures\ORM\LoadFakeData',
        ));
    }

    /**
     * @Given /^I am the(?: registered)? user named "(.*)" *$/
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
     * @Given /^I am level (\d+) *$/
     */
    public function iAmLevel($level)
    {
        if (empty($this->user)) {
            $this->fail("There's no `I` to level up. You don't exist !");
        }

        $this->user->setLevel(max(1, $level));
    }

    /**
     * @Given /^there is a user named "(.+)" *$/
     */
    public function thereIsAUserNamed($name)
    {
        $this->createUser($name);
    }

    /**
     * @Given /^there is a user with email "(.+)" *$/
     */
    public function thereIsAUserWithEmail($email)
    {
        $this->createUser($email, $email);
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
     * @Given /^(there is|I give|I spot) an item at (-?\d+\.\d*) ?, ?(-?\d+\.\d*)$/
     */
    public function thereIsAnItemAt($action, $latitude, $longitude)
    {
        $giver   = ($action == 'I give') ? $this->user : null;
        $spotter = ($action == 'I spot') ? $this->user : null;
        if (!empty($action) && empty($this->user)) {
            $this->fail("There is no I. You are no-one ; you cannot give.");
        }

        $this->createItem($latitude, $longitude, null, $giver, $spotter);
    }

    /**
     * @Given /^there are (\d+) items at (-?\d+\.\d*) ?, ?(-?\d+\.\d*)$/
     */
    public function thereAreItemsAt($howMany, $latitude, $longitude)
    {
        for ($i=0; $i<$howMany; $i++) {
            $this->thereIsAnItemAt('', $latitude, $longitude);
        }
    }

    /**
     * @Given /^I gave (\d+) items? *(?:(.+) ago)? *$/
     */
    public function iGaveItems($howMany, $when=null)
    {
        if (!empty($when)) {
            $when = new \DateTime("@".strtotime("-".$when));
        }

        for ($i=0; $i<$howMany; $i++) {
            $this->createItem(null, null, null, $this->user, null, $when);
        }
    }


    // ROUTES STEPS ////////////////////////////////////////////////////////////

    /**
     * @When /^I (?:try to )?give the following(?: item)? ?:$/
     */
    public function iGiveThatItem($pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $data['gift'] = true;
        $this->request('POST', 'item', $data);
    }

    /**
     * @When /^I (?:try to )give an item *$/
     */
    public function iGiveAnItem()
    {
        $lat = $this->faker->latitude;
        $lon = $this->faker->longitude;
        $pystring  = "location: $lat, $lon\n";
        $pystring .= "gift: true";
        $this->iPost('item', $pystring);
    }

    /**
     * @When /^I register the following ?:$/
     */
    public function iRegister($pystring='')
    {
        $this->iPost('register', $pystring);
    }


    // REQUEST STEPS ///////////////////////////////////////////////////////////

    /**
     * @When /^I GET ([^ ]+)(?: with(?: the parameters) *:)?$/i
     */
    public function iGet($route, $pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $this->request('GET', $route, $data);
    }

    /**
     * @When /^I POST to ([^ ]+) the following ?:$/i
     */
    public function iPost($route, $pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $this->request('POST', $route, $data);
    }

    /**
     * @When /^I POST to ([^ ]+) the file (.+)?$/i
     */
    public function iPostTheFile($route, $filePath)
    {
        // We need to make a copy of the file, 'cause it will be *moved*
        // Unless the test suite fails, it seems ? Something is fishy here...
        $sInfo = new \SplFileInfo($filePath);
        $extension = $sInfo->getExtension();
        $tmpFilePath = $sInfo->getBasename('.'.$extension).'_copy.'.$extension;

        $fInfo = new finfo;
        $mime = $fInfo->file($sInfo->getRealPath(), FILEINFO_MIME);

        copy($filePath, $tmpFilePath);

        $picture = new UploadedFile(
            $tmpFilePath,
            $sInfo->getFilename(),
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
     *
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
    public function thereShouldBeSomethingInTheDatabase($thatMuch, $what)
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
     * @Then /^the user (.+) should have (\d+) experience points?$/
     */
    public function theUserShouldHaveExperiencePoints($username, $experience)
    {
        $usr = $this->getEntityManager()
                    ->getRepository("Give2PeerBundle:User")
                    ->findOneBy(['username'=>$username])
                    ;

        $this->assertEquals($experience, $usr->getExperience());
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
     * @param string $email
     * @return User
     */
    protected function createUser($name, $email='')
    {
        $um = $this->getUserManager();

        if (empty($email)) $email = $name.'@give2peer.org';

        $user = $um->createUser();
        $user->setEmail($email);
        $user->setUsername($name);
        $user->setPlainPassword($name);
        $user->setEnabled(true);

        // This will canonicalize, encode, persist and flush
        $um->updateUser($user);

        return $user;
    }

    /**
     * Create a dummy item.
     *
     * @param null $latitude
     * @param null $longitude
     * @param null $title
     * @param null $giver
     * @param null $spotter
     * @param null $when
     */
    protected function createItem($latitude=null, $longitude=null, $title=null,
                                  $giver=null, $spotter=null, $when=null)
    {
        // Fill up attributes with default values, random ones
        if (null == $latitude)
            $latitude = $this->faker->latitude;
        if (null == $longitude)
            $longitude = $this->faker->longitude;
        if (null == $title)
            $title = sprintf("%s %s", $this->faker->colorName, $this->faker->word);

        // Create the item
        $item = new Item();
        $item->setTitle(substr($title, 0, 32));
        $item->setLocation("$latitude, $longitude");
        $item->setLatitude($latitude);
        $item->setLongitude($longitude);
        if ($giver)   $item->setGiver($giver);
        if ($spotter) $item->setSpotter($spotter);

        // Add the item to database
        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $em->persist($item);
        $em->flush();

        // Edit the creation timestamp
        if (null != $when) {
            $item->setCreatedAt($when);
            $em->flush();
        }
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