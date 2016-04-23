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
 * Also, should be stored elsewhere, like `extra_functions.php` and loaded via
 * `composer`. We need such a file to import top-level functions that could be
 * part of PHP itself anyways, and it's probably not such a big overhead.
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
 * This file is getting BIG.
 * Traits work well with FeatureContext.
 * 
 * Schedule a refactorization when this file gets more than 1111 lines !
 *
 * This prints a fortune cookie when it passes ; sugar for the mind.
 */
class FeatureContext extends    BaseContext
                     implements BehatContext, SnippetAcceptingContext
{
    /** @var string $version The version of the API to use */
    static $version = '1';

    /** @var Client $client */
    protected $client;

    /** @var Crawler $crawler */
    protected $crawler;

    /**
     * Per scenario, usually "I".
     * @var User $user
     */
    protected $user;

    /**
     * The user class does not have a getPassword method and we need to
     * sometimes override the fact that all of our created users have the same
     * password as username.
     *
     * In the steps to change the password, for instance.
     *
     * @var String $password
     */
    protected $password;

    /**
     * Per scenario, usually "that item".
     * @var Item $item
     */
    protected $item;

    /**
     * Generate any type of data you want.
     * We added `latitude` and `longitude` :
     * Use it like this :
     * `$lat = $this->faker->latitude;`
     * List of properties and methods faker provides :
     * https://github.com/fzaninotto/Faker#formatters
     * @var Generator $faker
     */
    protected $faker;

    public function __construct()
    {
        $this->faker = FakerFactory::create();
        $this->faker->addProvider(new GeolocationFaker($this->faker));
    }

    /**
     * Finds the directory where the phpunit.xml(.dist) is stored.
     *
     * Motherfuckers bound the kernel creation to phpunit -_-
     * In response, we're dirtying our code with this hax.
     * We can simply set $_SERVER['KERNEL_DIR'] = 'app'; instead of this.
     *
     * See https://github.com/liip/LiipFunctionalTestBundle/pull/255 too
     *
     * @return string The directory where phpunit.xml(.dist) is stored
     */
//    protected static function getPhpUnitXmlDir()
//    {
//        return 'app';
//    }

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
        try {

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

        } catch (\Doctrine\DBAL\DBALException $e) {
            echo "Database schema is probably not set up !";
        }

        // Loading an empty array still truncates all tables.
        $this->loadFixtures(array());

        // Empty the public directory where pictures are -- todo
        // THIS IS DANGEROUS !
        // It means that this test suite can never EVER be run on the prod server
        // This is BAD.
        // So, no. I'll move some things to configuration, and we'll try again.
        // Meanwhile, just delete by hand the files created in web/pictures
    }

    /**
     * To train our inner pigeon into enjoying Feature-Driven Development...
     * @AfterSuite
     */
    public static function gimmeCookieNomNomNom(AfterSuiteScope $scope)
    {
        // make it a meme : a fortune cookie each time the suite runs okay
        if ($scope->getTestResult()->isPassed()) {
            try { print(shell_exec('fortune -a')); } catch (\Exception $e) {}
        }
    }


    // TRANSFORMERS ////////////////////////////////////////////////////////////

    /**
     * A very handy transformer for integers, registered to Behat.
     * 
     * This could be improved to add support for more idiomatic numbers like
     * "one", "a", "forty-two". Not easy to match with a regex !?
     * 
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
     * @Then I blaze through darkness and light alike
     */
    public function iBlazeThroughDarknessAndLightAlike() {}

    /**
     * @Given I print :arg1
     */
    public function iPrint($arg1) { print($arg1); }

    /**
     * Useful for quick'n dirty debugging.
     * @Then /^I (?:print|dump) the response$/
     */
    public function iDumpTheResponse()
    {
        if (empty($this->client)) {
            $this->fail("No client. Request something first.");
        }
        $content = $this->client->getResponse()->getContent();
        try {
            $content = json_encode(json_decode($content), JSON_PRETTY_PRINT);
        } catch (\Exception $e) {}

        print($content."\n");
    }

    /**
     * Useful for quick'n dirty debugging.
     * @Then /^I (?:print|dump) myself$/
     */
    public function iDumpMyself()
    {
        if (empty($this->user)) {
            $this->fail("No I. Be someone first.");
        }
        
        try {
            print(json_encode($this->getI(), JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->fail("Nope.");
        }
    }

    /**
     * Useful for quick'n dirty debugging.
     * @Then /^I (?:print|dump) that item$/
     */
    public function iDumpThatItem()
    {
        if (empty($this->item)) {
            $this->fail("No \"that item\". Make one first.");
        }

        try {
            print(json_encode($this->item, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->fail("Nope.");
        }
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
     * @Given /^I am not (?:authenticated|logged in)$/
     */
    public function iAmNotAuthenticated()
    {
        $this->user = null;
    }

    /**
     * @Given /^I am (?:the|a)(?: registered)? user named "?(.*?)"? *$/
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
     * @Given /^my password is "(.+)"$/
     */
    public function myPasswordIs($password)
    {
        $user = $this->getI()->setPlainPassword($password);
        $this->getUserManager()->updateUser($user);
//        $this->getEntityManager()->flush();

        $this->password = $password;
    }

    /**
     * @Given /^I am level (\d+) *$/
     */
    public function iAmLevel($level)
    {
        $this->getI()->setLevel(max(0, $level));
        $this->getEntityManager()->flush();
    }

    /**
     * @Given /^there is a user named "?(.+?)"? *$/
     */
    public function thereIsAUserNamed($name)
    {
        $this->createUser($name);
    }

    /**
     * @Given /^there is a user (?:with|whose) email (?:is )?"?(.+?)"? *$/
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
     * @Given /^there is an item at (-?\d+\.\d*) ?, ?(-?\d+\.\d*)$/
     */
    public function thereIsAnItemAt($latitude, $longitude)
    {
        $this->createItem(null, $latitude, $longitude);
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


    // ROUTES STEPS ////////////////////////////////////////////////////////////

    /**
     * @When /^I should (fail|succeed) to authenticate(?: with password "(.+)")?$/
     */
    public function iFailOrSucceedToAuthenticate($which, $password)
    {
        $backup = null;
        if (null != $this->password) {
            $backup = $this->password;
            $this->password = $password;
        }

        $this->request('GET', 'ping');
        
        switch ($which) {
            case 'fail':
                $this->assertRequestFailure();
                break;
            case 'succeed':
                $this->assertRequestSuccess();
                break;
            default:
                $this->fail("?");
        }

        if (null != $backup) {
            $this->password = $backup;
        }
    }
    
    /**
     * @When /^I request my profile information$/
     */
    public function iGetMyProfile()
    {
        $this->request('GET', 'profile');
    }
    
    /**
     * @When /^I request the profile information of (.+)$/
     */
    public function iGetTheProfileOf($username)
    {
        $this->request('GET', 'profile/'.$username);
    }
    
    /**
     * @When /^I request the tags$/
     */
    public function iGetThetags()
    {
        $this->request('GET', 'tags');
    }
    
    /**
     * @When /^I (?:try to )?g[ai]ve the following(?: item)? ?:$/
     */
    public function iGiveThatItem($pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $this->request('POST', 'item', $data);
    }

    /**
     * @When /^I (?:try to )?g[ai]ve the following(?: item)? *(.+) ago ?:$/
     */
    public function iGaveThatItemAgo($when=null, $pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $this->request('POST', 'item', $data);

        if (!empty($when)) {
            $this->setLastGivenItemCreationDate($when);
        }
    }

    /**
     * @When /^I (?:try to )?give an(?:other)? item *$/
     */
    public function iGiveAnItem()
    {
        $this->iGiveAnItemAt($this->faker->latitude, $this->faker->longitude);
    }

    /**
     * @Given /^I (?:try to )?give an item at (-?\d+\.\d*) ?, ?(-?\d+\.\d*)$/
     */
    public function iGiveAnItemAt($latitude, $longitude)
    {
        $title = sprintf("%s %s", $this->faker->colorName, $this->faker->word);
        $pystring  = "location: $latitude, $longitude\n";
//        $pystring .= "title: $title\n";
//        $pystring .= "description: $title\n";
        $this->iPost('item', $pystring);
    }

    /**
     * WARNING :
     * THIS WILL NOT INCREMENT THE AUTHOR'S KARMA
     * AND IT BYPASSES THE QUOTA SYSTEM (it will never fail)
     * -- THIS IS INTENTIONAL.
     * It does count for subsequent quota checks, which was the intent.
     *
     * @Given /^I already gave (\d+) items? *(?:(.+) ago)? *$/
     */
    public function iAlreadyGaveItemsBypassingQuotas($howMany, $when=null)
    {
        if (!empty($when)) {
            $when = new \DateTime("@".strtotime("-".$when));
        }

        $author = $this->getI();
        for ($i=0; $i<$howMany; $i++) {
            $this->createItem($author, null, null, null, $when);
        }
    }

    /**
     * @Given /^I gave (\d+) items? *(?:(.+) ago)? *$/
     */
    public function iGaveItems($howMany, $when=null)
    {
        for ($i=0; $i<$howMany; $i++) {
            $lat = $this->faker->latitude;
            $lng = $this->faker->longitude;
            $data = [
                'location' => "$lat / $lng",
            ];
            $this->request('POST', 'item', $data);
            $this->theRequestShouldBeAcceptedOrDenied('accepted');

            if (!empty($when)) {
                $this->setLastGivenItemCreationDate($when);
            }
        }
    }

    /**
     * @When /^I (?:try to )?delete the item titled "(.+)"$/
     */
    public function iDeleteTheItemTitled($title)
    {
        $item = $this->getItemRepository()->findOneBy(['title' => $title]);

        $this->iDelete('item/'.$item->getId());
    }

    /**
     * @When /^I register the following ?:$/
     */
    public function iRegister($pystring='')
    {
        $this->iPost('register', $pystring);
    }

    /**
     * @When /^I change my password to "(.+)"$/
     */
    public function iChangeMyPassword($password)
    {
        $this->request('POST', "password/change", [
            'password' => $password
        ]);

        $this->password = $password;
    }

    /**
     * @When /^I request the statistics$/
     */
    public function iRequestStats()
    {
        $this->iGet('stats');
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
     * @When /^I DELETE ([^ ]+)(?: with(?: the parameters) *:)?$/i
     */
    public function iDelete($route, $pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $this->request('DELETE', $route, $data);
    }

    /**
     * @When /^I POST to ([^ ]+) the file (.+)?$/i
     */
    public function iPostTheFile($route, $filePath)
    {
        
        // 1. We need to make a copy of the file, 'cause it will be *moved*
        // Unless the test suite fails, it seems ? Something is fishy here...
        // 2. (months later) Errrr... Trying to unlink() now, as copies stay ?
        // It is a failure, we need to unlink later on. Can't do that here.
        // 3. (seconds later) We'll make copies in the cache directory, whatever
        // We probably don't need copies anymore ; ... ... ... ... ..... meh.

        $sInfo = new \SplFileInfo($filePath);
        $extension = $sInfo->getExtension();

        $tmpPath = join(DIRECTORY_SEPARATOR, ['app', 'cache', 'test', 'pics']);
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath, 0777, true);
        }

        $tmpPath .= DIRECTORY_SEPARATOR;
        $tmpPath .= $sInfo->getBasename('.'.$extension).'_copy.'.$extension;

        $fInfo = new finfo;
        $mime = $fInfo->file($sInfo->getRealPath(), FILEINFO_MIME);

        copy($filePath, $tmpPath);

        $picture = new UploadedFile(
            $tmpPath,
            $sInfo->getFilename(),
            $mime,
            filesize($filePath),
            UPLOAD_ERR_OK,
            true // test mode ?
        );
        $files = ['picture' => $picture];

        $this->request('POST', $route, [], $files);
        
        //unlink($tmpPath); // nope
    }


    // RESPONSE STEPS //////////////////////////////////////////////////////////
    
    /**
     * @Then /^the request should be (accepted|denied)$/
     */
    public function theRequestShouldBeAcceptedOrDenied($which)
    {
        switch ($which) {
            case 'accepted':
                $this->assertRequestSuccess();
                break;
            case 'denied':
                $this->assertRequestFailure();
                break;
            default:
                $this->fail("Élu, aimé, jeté, ô poète ! Je miaule !");
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
     * /!\ Will count items marked for deletion too !
     *
     * @Then /^there should (?:still )?be (\d+) items? (?:[ie]n)?titled "(.+)"$/
     */
    public function thereShouldBeAnItemTitled($count, $title)
    {
        // Will return items marked for deletion too
        $items = $this->getItemRepository()->findBy(['title' => $title]);

        $this->assertEquals($count, count($items));
    }

    /**
     * @Then /^the user (.+) should be level (\d+)$/
     */
    public function theUserShouldBeLevel($username, $level)
    {
        $usr = $this->getUser($username);

        $this->assertEquals($level, $usr->getLevel());
    }

    /**
     * @Then /^I should be level (\d+)$/
     */
    public function iShouldBeLevel($level)
    {
        $this->assertEquals($level, $this->getI()->getLevel());
    }

    /**
     * @Then /^the user (.+) should have (\d+) karma points?$/
     */
    public function theUserShouldHaveKarmaPoints($username, $karma)
    {
        $usr = $this->getUser($username);

        $this->assertEquals($karma, $usr->getKarma());
    }

    /**
     * @Then /^my quota for adding items (?:is|should (?:still )?be) (\d+)$/
     */
    public function myQuotaForAddingItemsShouldBe($quota)
    {
        $a = $this->getItemRepository()->getAddItemsCurrentQuota($this->getI());
        $this->assertEquals($quota, $a);
    }

    /**
     * @Then /^I should (?:still )?be the author of (\d+) items?$/
     */
    public function iShouldBeTheAuthorOf($count)
    {
        // Not sure which is best
        //$actual = count($this->getI()->getItemsAuthored());
        $items = $this->getItemRepository()->findAuthoredBy($this->getI());
        
        $actual = count($items);

        $s = json_encode($items, JSON_PRETTY_PRINT);

        $this->assertEquals($count, $actual, "Got $actual items !\n$s");
    }

    /**
     * @Then /^I should (?:still )?have (\d+) items? in my profile$/
     */
    public function iShouldHaveItemsInMyProfile($count)
    {
        $actual = $this->getItemRepository()
            ->countAuthoredBy($this->getI());

        $this->assertEquals($count, $actual);
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
     * Handy tool to hack Items `createdAt` field after adding them via the API.
     * Requires that a request to POST /item was made last.
     * We grab the item id from the response and tweak its creation date
     * directly in the database.
     * @param $when
     */
    protected function setLastGivenItemCreationDate($when)
    {
        $when = new \DateTime("@".strtotime("-".$when));
        $id = null;
        $content = $this->client->getResponse()->getContent();
        try {
            $ob = json_decode($content);
            $id = $ob->item->id;
        } catch (\Exception $e) {
            $this->fail("Not the response we expected when hacking createdAt\n".
                "This requires that a request to POST `/item` was made last.".
                "Response obtained : $content\n".
                $e->getMessage()."\n".
                $e->getTraceAsString());
        }
        if ($id == null) {
            $this->fail("Nope, nope, NOPE. That's NOT okay ! No id ?!?");
        } else {
            $em = $this->getEntityManager();
            /** @var Item $item */
            $item = $em->getRepository("Give2PeerBundle:Item")->find($id);
            $item->setCreatedAt($when);
            $em->flush();
        }
        

    }

    /**
     * @param $username
     * @return null|User
     */
    protected function getUser($username)
    {
        return $this->getEntityManager()
            ->getRepository("Give2PeerBundle:User")
            ->findOneBy(['username'=>$username]);
    }

    /**
     * Get the user described as "I" in the steps, if one was defined.
     * Note: grabs a "fresh" copy of the user from the database.
     * 
     * @return User
     */
    protected function getI()
    {
        if (empty($this->user)) {
            $this->fail(
                "There is no I. Define yourself first, with a step such as :\n".
                "Given I am a user named \"Tester\"");
        }

        // This does not refresh user karma ? It's not recommended anyways...
        // $um = $this->getUserManager();
        // $um->refresh($this->user);

        // We want a fresh user from database, because we might have made some
        // requests with this user between its creation and now. Karma changes
        return $this->getUser($this->user->getUsername());
    }

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

//        return $user; // Doctrine says it is not managed ???
        return $this->getUser($user->getUsername());
    }

    /**
     * Create a dummy item.
     *
     * WARNING: THIS WILL NOT GIVE KARMA TO THE AUTHOR
     *          (but it will count for quotas)
     *
     * @param null $latitude
     * @param null $longitude
     * @param null $title
     * @param null $author
     * @param null $when
     */
    protected function createItem($author=null, $latitude=null, $longitude=null,
                                  $title=null,  $when=null)
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
        $item->setAuthor($author);

        // Add the item to database
        /** @var EntityManager $em */
        $em = $this->getEntityManager();
//        $em->refresh($author); // hmmm...
//        $this->getUserManager()->refreshUser($author);
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
        if (0 !== strpos($uri, '/')) {
            $uri = '/' . $uri;
        }
        $uri = '/v' . self::$version . $uri;

        $this->client = $this->getOrCreateClient();

        if (!empty($this->user)) {

            if (null != $this->password) {
                $password = $this->password;
            } else {
                $password = $this->user->getUsername();
            }

            $server['PHP_AUTH_USER'] = $this->user->getUsername();
            $server['PHP_AUTH_PW']   = $password;
        }

        $this->crawler = $this->client->request(
            $method, $uri, $parameters, $files,
            $server, $content, $changeHistory
        );

        return $this->crawler;
    }


    public function assertRequestSuccess()
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        $content = $this->client->getResponse()->getContent();
        // Good try, but it floods the console too much :(
        //try {
        //    $content = json_encode(json_decode($content), JSON_PRETTY_PRINT);
        //} catch (\Exception $e) {}

        if (!$this->client->getResponse()->isSuccessful()) {
            $this->fail(
                sprintf("Response is unsuccessful, with '%d' HTTP status code ".
                    "and the following content:\n%s",
                    $this->client->getResponse()->getStatusCode(),
                    $content));
        }
    }

    public function assertRequestFailure()
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        $content = $this->client->getResponse()->getContent();
        // Good try, but it floods the console too much :(
        //try {
        //    $content = json_encode(json_decode($content), JSON_PRETTY_PRINT);
        //} catch (\Exception $e) {}

        if ($this->client->getResponse()->isSuccessful()) {
            $this->fail(
                sprintf("Response is successful, with '%d' HTTP status code ".
                    "and the following content:\n%s",
                    $this->client->getResponse()->getStatusCode(),
                    $content));
        }
    }
}