<?php

// namespace Give2PeerFeatures; // then Behat cannot find the FeatureContext :(

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\ItemPicture;
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
 * Please note that we had to make our own behat runner wrapper in order to
 * solve the `You must override the KernelTestCase::createKernel() method.`.
 * It is available in `script/behat`, and it should replace the default runner
 * automatically thanks to our post-update listener specified in composer.
 * 
 * This file is getting BIG.
 * Traits work well with FeatureContext.
 * Schedule a refactorization when this file gets more than 3333 lines !
 *
 * This prints a fortune cookie when it passes ; sugar for the mind.
 */
class FeatureContext extends    BaseContext
                     implements BehatContext
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
     * Used in the steps to change the password, mostly.
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
        // This is more complicated than it should, because of my noobish pgSQL-fu
        $tables = [
            'Peer', // User is named Peer in the database, as User is reserved
            'Item',
            'ItemPicture',
            'Tag',
            'Thank',
            'Report',
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

        // Empty the public directory where item pictures are -- depends on env
        $pics_dir = $this->getParameter('give2peer.items.pictures.directory');
        $this->removeDirectory($pics_dir, false);
        //print("Removed directory $pictures_dir");
    }


    /**
     * Clean up after ourselves.
     * @AfterScenario
     */
    public function emptyPicturesDirectory(AfterScenarioScope $scope)
    {
        // Empty the public directory where item pictures are -- depends on env
        $pics_dir = $this->getParameter('give2peer.items.pictures.directory');
        $this->removeDirectory($pics_dir, false);
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
     * @Then it should not raise an exception
     */
    public function itShouldNotRaiseAnException() {}

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
        $response = $this->client->getResponse();
        $content = $response->getContent();
        try {
            $content = json_encode(json_decode($content), JSON_PRETTY_PRINT);
        } catch (\Exception $e) {}

        print($response->getStatusCode() . "\n" . $content . "\n");
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
     * @Given /^I am (?:the|a)(?: registered)? user named "?(.*?)"? *(?:of level (\d+))? *$/
     */
    public function iAmTheRegisteredUserNamed($name, $level=null)
    {
        $um = $this->getUserManager();
        $user = $um->findUserByUsername($name);

        if (empty($user)) $user = $this->createUser($name);
        if (null !== $level) $this->someoneIsLevel($name, $level);

        $this->user = $user;
    }

    /**
     * @Given /^my password is "(.+)"$/
     */
    public function myPasswordIs($password)
    {
        $user = $this->getI()->setPlainPassword($password);
        $this->getUserManager()->updateUser($user);
        // Flushing was already done by the user manager.
        //$this->getEntityManager()->flush();

        $this->password = $password;
    }

    /**
     * @Given /^I am level (\d+) *$/
     */
    public function iAmLevel($level)
    {
        $this->someoneIsLevel($this->getI()->getUsername(), $level);
    }

    /**
     * @Given /^"?(.+)"? is level (\d+) *$/
     */
    public function someoneIsLevel($someone, $level)
    {
        $user = $this->getUser($someone);
        $user->setLevel($level);
        $this->getEntityManager()->flush();
    }

    /**
     * @Given /^I gained the daily karma (.+)$/
     */
    public function iGainedDailyKarmaWhen($when)
    {
        $when = new \DateTime("@".strtotime("-".$when));
        $this->getI()->setDailyKarmaAt($when);
        $this->getEntityManager()->flush();
    }

    /**
     * @Given /^there is a user named "?(.+?)"? *(?:of level (\d+))?$/
     */
    public function thereIsANamedUserOfLevel($name, $level=null)
    {
        $this->createUser($name);
        if (null !== $level) $this->someoneIsLevel($name, $level);
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

    /**
     * @When /^(.+) added without karma gain an item titled "(.+)"$/
     */
    public function someoneAddedAnItemTitled($user, $title)
    {
        $user = $this->getUser($user);

        if (null == $user) {
            $this->fail("User $user could not be found.");
        } // refactor into getMandatoryUser() or getUser($user, $require=true)

        $this->createItem($user, null, null, $title, null);
    }

    /**
     * Will find soft-deleted items too.
     *
     * @When /^the item titled "(.+)" is hard deleted *$/
     */
    public function theItemIsHardDeleted($title)
    {
        // We want to find amongst the soft-deleted items too
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('softdeleteable');
        $item = $this->getItemByTitle($title);
        $filters->enable('softdeleteable');

        $this->getItemRepository()->hardDeleteItem($item);
    }


    // ROUTES STEPS ////////////////////////////////////////////////////////////


    /**
     * @When /^I greet the server in "(.+)"$/
     */
    public function iGreetTheServer($lang)
    {
//        $this->request('GET', "/test/greet", ['_locale'=>$lang]);
        $this->request('GET', "/test/greet", [], [], ['HTTP_ACCEPT_LANGUAGE'=>$lang]);
    }

    /**
     * @When /^I should (fail|succeed) to authenticate(?: with password "(.+)")?$/
     */
    public function iFailOrSucceedToAuthenticate($which, $password)
    {
        $backup = null;
        if (null != $this->password) {
            $backup = $this->password;
        }
        $this->password = $password;

        $this->request('GET', 'check');
        
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
        $this->request('GET', 'user');
    }

    /**
     * @When /^I run the (.+) CRON task$/
     */
    public function iRunTheCronTask($frequency)
    {
        $this->request('GET', "cron/$frequency");
    }
    
    /**
     * @When /^I request the profile information of the user named (.+)$/
     */
    public function iGetTheProfileByUsername($username)
    {
        $id = $this->getUser($username)->getId();
        $this->request('GET', "user/$id", []);
    }
    
    /**
     * @When /^I request the profile information of the user #(\d+)$/
     */
    public function iGetTheProfileById($id)
    {
        $this->request('GET', "user/$id", []);
    }

    /**
     * @When /^I (?:try to )?update my profile information with the following ?:$/
     */
    public function iUpdateMyProfile($pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $this->request('POST', 'user/'.$this->getI()->getId(), $data);
    }
    
    /**
     * @When /^I request the tags$/
     */
    public function iGetThetags()
    {
        $this->request('GET', 'tags');
    }

    /**
     * Big regex for such small numbers !
     * @When /^I (?:try to )?request the items around ([+-]?\d+(?:[.,]\d*)?|[+-]?[.,]\d+) *[,\/] ([+-]?\d+(?:[.,]\d*)?|[+-]?[.,]\d+)$/
     */
    public function iRequestTheItemsAround($lat, $lng)
    {
        $this->request('GET', "items/around/$lat/$lng");
    }

    /**
     * @When /^I get the details of the item titled "(\w+)"$/
     */
    public function iGetTheDetailsOfItemTitled($title)
    {
        $item = $this->getItemByTitle($title);
        $id = $item->getId();
        $this->request('GET', "item/${id}");
    }

    /**
     * @Then /^I should (?:still |now )?see (\d+) items?$/
     */
    public function iShouldSeeItems($itemsCount)
    {
        if ($itemsCount > 64) {
            throw new Exception(
                "This step does not support exceeding the item-finding limit."
            );
        }
        $this->request('GET', "items/around/0.0/0.0");
        $response = $this->getJsonResponse();
        $this->assertEquals($itemsCount, count($response->items));
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
     * @When /^I (?:try to )?g[ai]ve an(?:other)? item *$/
     */
    public function iGiveAnItem()
    {
        $this->iGiveAnItemAt($this->faker->latitude, $this->faker->longitude);
    }

    /**
     * @Given /^I (?:try to )?give an item at (-?\d+\.\d*) ?[,\/] ?(-?\d+\.\d*)$/
     */
    public function iGiveAnItemAt($latitude, $longitude)
    {
        $title = sprintf("%s %s", $this->faker->colorName, $this->faker->word);
        $pystring  = "location: $latitude, $longitude\n";
        $this->iPost('item', $pystring);
    }

    /**
     * @Given /^I (?:try to )?g[ai]ve an item titled "?(.+?)"?$/
     */
    public function iGiveAnItemTitled($title)
    {
        $location = sprintf("%s/%s", $this->faker->lat, $this->faker->lng);
        $pystring  = "location: $location\n";
        $pystring .= "title: $title\n";
        $this->iPost('item', $pystring);
    }

    /**
     * WARNING :
     * THIS WILL NOT INCREMENT THE AUTHOR'S KARMA
     * AND IT BYPASSES THE QUOTA SYSTEM (it will never fail)
     * -- THIS IS INTENTIONAL.
     * It does count for subsequent quota checks, which was the intent.
     *
     * @Given /^I already (?:gave|added) (\d+) items? *(?:(.+) ago)? *$/
     */
    public function iAlreadyGaveItemsBypassingQuotas($howMany, $when=null)
    {
        if ( ! empty($when)) {
            $when = new \DateTime("@".strtotime("-".$when));
        }

        $author = $this->getI();
        for ($i=0; $i<$howMany; $i++) {
            $this->createItem($author, null, null, null, $when);
        }
    }

    /**
     * @Given /^I gave (\d+) items? *(?:(.+) ago)?$/
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
     * @When /^I (?:try to )?deleted? the item titled "(.+)"$/
     */
    public function iDeleteTheItemTitled($title)
    {
        $item = $this->getItemByTitle($title);
        $this->iDelete('item/'.$item->getId());
    }

    /**
     * @When /^I pre-upload(?:ed)? the image file ([^ ]+) *(?:(.+) ago)?$/i
     */
    public function iPreUploadTheImageFile($filePath, $when=null)
    {
        $this->iPostTheFile("/item/picture", $filePath);
        if (null != $when) $this->setLastGivenItemPictureCreationDate($when);
    }

    /**
     * @When /^I thank the author of the item titled "(.+)"$/
     */
    public function iThankTheAuthorOfItemTitled($title)
    {
        $item = $this->getItemByTitle($title);
        $this->request('POST', "/item/".$item->getId()."/thank", []);
    }

    /**
     * @When /^I (?:try to )?report the item titled "(.+)" as abusive$/
     */
    public function iReportTheItemAsAbusive($title)
    {
        $item = $this->getItemByTitle($title);
        $this->request('POST', "/item/".$item->getId()."/report", []);
    }

    /**
     * Will find soft-deleted items too.
     *
     * @When /^I cancel my report on the item titled "(.+)" *$/
     */
    public function iCancelMyReportOnTheItem($title)
    {
        // We want to find amongst the deleted items too
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('softdeleteable');
        $item = $this->getItemByTitle($title);
        $filters->enable('softdeleteable');

        $this->request('POST', "/item/".$item->getId()."/report", ['cancel'=>true]);
    }

    /**
     * @When /^I (?:try to )?pre-register$/
     */
    public function iPreRegister()
    {
        $this->iPost('user', '');
    }

    /**
     * @When /^I (?:try to )?register the following ?:$/
     */
    public function iRegister($pystring='')
    {
        $this->iPost('user', $pystring);
    }

    /**
     * @When /^I (?:try to )?change my email to (.+)$/
     */
    public function iChangeMyEmail($email)
    {
        $id = $this->getI()->getId();
        $this->request('POST', "user/$id/email", [
            'email' => $email
        ]);
    }

    /**
     * @When /^I (?:try to )?change my username to (.+)$/
     */
    public function iChangeMyUsername($username)
    {
        $id = $this->getI()->getId();
        $this->request('POST', "user/$id/username", [
            'username' => $username
        ]);

        // also update context variables on success otherwise getI() is broken
        // (only for the rest of the scenario, but still...)
        if ($this->client->getResponse()->isOk()) {
            $this->user->setUsername($username);
        }
    }

    /**
     * @When /^I (?:try to )?change my password to "(.+)"$/
     */
    public function iChangeMyPassword($password)
    {
        $id = $this->getI()->getId();
        $this->request('POST', "user/$id/password", [
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
     * @When /^I (?:GET|get) ([^ ]+)(?: with(?: the parameters) *:)?$/i
     */
    public function iGet($route, $pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $this->request('GET', $route, $data);
    }

    /**
     * @When /^I (?:POST|post) to ([^ ]+) the following ?:$/i
     */
    public function iPost($route, $pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $this->request('POST', $route, $data);
    }

    /**
     * @When /^I (?:DELETE|delete) ([^ ]+)(?: with(?: the parameters) *:)?$/i
     */
    public function iDelete($route, $pystring='')
    {
        $data = empty($pystring) ? [] : $this->fromYaml($pystring);
        $this->request('DELETE', $route, $data);
    }

    /**
     * @When /^I (?:POST|post) to ([^ ]+) the file (.+)?$/i
     */
    public function iPostTheFile($route, $filePath)
    {
        
        // 1. We need to make a copy of the file, 'cause it will be *moved*
        // Unless the test suite fails, it seems ? Something is fishy here...
        // 2. (months later) Errrr... Trying to unlink() now, as copies stay ?
        // It is a failure, we need to unlink later on. Can't do that here.
        // 3. (seconds later) We'll make copies in the cache directory, whatever
        // We probably don't need copies anymore ; ... ... ... ... ..... meh.
        // 4. (some time later) If you read this, don't be afraid. All's well.

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

        if (is_file($tmpPath)) unlink($tmpPath);
    }


    // RESPONSE STEPS //////////////////////////////////////////////////////////
    
    /**
     * @Then /^(?:the|my) request (?:should (?:be|have been)|was) (accepted|denied)$/
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
     * @Then /^the response should((?: not)?) contain "(.+)"$/
     */
    public function theResponseShouldContain($not='', $that='')
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        $response = $this->client->getResponse();
        $actual = $response->getContent();

        $found = (false !== mb_strpos($actual, $that));

        if (empty($not) && !$found) {
            $this->fail(sprintf(
                "The response did not include the following:\n%s\n" .
                "Because the response provided:\n%s",
                print_r($that, true),
                print_r($actual, true)
            ));
        }

        if (!empty($not) && $found) {
            $this->fail(sprintf(
                "The response actually included the following:\n%s\n" .
                "Because the response provided:\n%s",
                print_r($that, true),
                print_r($actual, true)
            ));
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
     * @Then /^there should (?:only )?be (\d+) items? in the response$/
     */
    public function thereShouldBeItemsInTheResponse($howMany)
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        $response = $this->client->getResponse();
        $actual = json_decode($response->getContent());

        if (count($actual->items) != $howMany) {
            $this->fail(sprintf(
                "The response sent %d item(s) back,\n" .
                "Because the response provided:\n%s",
                print_r(count($actual), true),
                print_r($actual, true)
            ));
        }
    }

    /**
     * @Then /^there should be (\d+) tags? in the response$/
     */
    public function thereShouldBeTagsInTheResponse($howMany)
    {
        if (empty($this->client)) {
            throw new Exception("No client. Request something first.");
        }

        $response = $this->client->getResponse();
        $actual = json_decode($response->getContent());

        if (count($actual->tags) != $howMany) {
            $this->fail(sprintf(
                "The response sent %d tags(s) back,\n" .
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
     * @Then /^there should (?:still )?be (\d+) items? (?:[ie]n)?titled "(.+)"$/
     */
    public function thereShouldBeAnItemTitled($count, $title)
    {
        // Will return items marked for deletion too
        $items = $this->getItemRepository()->findBy(['title' => $title]);

        $this->assertEquals($count, count($items));
    }

    /**
     * @Then /^there should ((?:not )?)be a user named (.+)$/
     */
    public function thereShouldBeUserNamed($not, $username)
    {
        $usr = $this->getUser($username);

        if (empty($not)) {
            $s = "There is no user named '$username', yet we expected one.";
            $this->assertTrue(null != $usr, $s);
            // definitely too verbose for our ORM-driven entities
            //$this->assertNotNull($usr, $s);
        } else {
            $s = "There is user named '$username', yet we expected none.";
            $this->assertTrue(null == $usr, $s);
            // definitely too verbose for our ORM-driven entities
            //$this->assertNull($usr, $s);
        }
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
     * @Then /^(?:the user )?(.+) should (?:still |now )?have (\d+) karma points?$/
     */
    public function theUziShouldHaveKarmaPoints($username, $karma)
    {
        if ( $username == "I") {
            $user = $this->getI();
        } else {
            $user = $this->getUser($username);
        }

        $this->assertEquals($karma, $user->getKarma());
    }

    /**
     * @Then /^my quota for adding items (?:is|should (?:still )?be) ([0-9]+)$/
     */
    public function myQuotaForAddingItemsShouldBe($quota)
    {
        $a = $this->getItemRepository()->getAddItemsCurrentQuota($this->getI());
        $this->assertEquals($quota, $a);
    }

    /**
     * @Then /^my email should (?:still )?((?:not )?)be (.+)$/
     */
    public function myEmailShouldStillBe($not, $email)
    {
        $actual = $this->getI()->getEmailCanonical();
        if (empty($not)) {
            $this->assertEquals($email, $actual);
        } else {
            $this->assertNotEquals($email, $actual);
        }
    }

    /**
     * @Then /^I should (?:still )?be the author of (\d+) items?$/
     */
    public function iShouldBeTheAuthorOf($count)
    {
        $items = $this->getItemRepository()->findAuthoredBy($this->getI());
        $s = json_encode($items, JSON_PRETTY_PRINT);

        $actual = count($items);
        $this->assertEquals($count, $actual, "Got $actual items !\n$s");

        // THAT ONE IS BROKEN OMG OMG OMG
        // SoftDeleteable filter, Y U NO DO DIS ?
//        $actual = count($this->getI()->getItemsAuthored());
//        $this->assertEquals($count, $actual, "Got $actual items !\n$s");
    }

    /**
     * @Then /^I should (?:still )?have (\d+) items? in my profile$/
     */
    public function iShouldHaveItemsInMyProfile($count)
    {
        $actual = $this->getItemRepository()->countAuthoredBy($this->getI());
        $this->assertEquals($count, $actual);
    }

    /**
     * @Then /^there should(?: still)?((?: not)?) be a file at (.*?) *$/
     */
    public function thereShouldBeAFileAt($not, $path)
    {
        $not = ($not != '');
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
     * Handy tool to hack Items `createdAt` field after adding them via the API,
     * so we can add them in the past, or even (gasp!) in the future.
     * Requires that a request to POST /item was made last.
     * We grab the item id from the response and tweak its creation date
     * directly in the database.
     * @param $when
     */
    protected function setLastGivenItemCreationDate($when)
    {
        $this->setLastGivenEntityCreationDate($when, 'Item', 'item', true);
    }

    /**
     * Handy tool to hack a PictureItem `createdAt` field after adding them via
     * the API, so we can add them in the past, or even (gasp!) in the future.
     * Requires that a request to POST /item/picture was made last.
     * We grab the item id from the response and tweak its creation date
     * directly in the database.
     * @param $when
     */
    protected function setLastGivenItemPictureCreationDate($when)
    {
        $this->setLastGivenEntityCreationDate($when, 'ItemPicture', 'picture');
    }

    /**
     * Handy tool to hack an Entity `createdAt` field after adding them via
     * the API, so we can add them in the past, or even (gasp!) in the future.
     * Requires that an appropriate request was made last.
     * We grab the item id from the response and tweak its creation date
     * directly in the database.
     * @param $when
     * @param $entity
     * @param $key
     * @param bool $updatedAtToo
     */
    protected function setLastGivenEntityCreationDate($when, $entity, $key, $updatedAtToo=false)
    {
        $when = new \DateTime("@".strtotime("-".$when));
        $id = null;
        $content = $this->client->getResponse()->getContent();
        try {
            $ob = json_decode($content, true);
            $id = $ob[$key]['id'];
        } catch (\Exception $e) {
            $this->fail("Not the response we expected when hacking createdAt\n".
                "This requires that a request to a route that returned an " .
                "$entity was made last.\n".
                "Response obtained :$content\n".
                $e->getMessage()."\n".
                $e->getTraceAsString());
        }
        if ($id == null) {
            $this->fail("No id in response :$content");
        } else {
            $em = $this->getEntityManager();
            /** @var \Gedmo\Timestampable\Traits\TimestampableEntity $ent */
            $ent = $em->getRepository("Give2PeerBundle:$entity")->find($id);
            $ent->setCreatedAt($when);
            if ($updatedAtToo) $ent->setUpdatedAt($when);
            $em->flush();
        }
    }

    /**
     * Get the user from its username, or null when not found.
     *
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
     * Get the item from its unique title, or fail.
     *
     * @param String $title
     * @return Item
     */
    protected function getItemByTitle($title)
    {
        /** @var Item $item */
        $item = $this->getItemRepository()->findOneBy(['title' => $title]);
        if (null == $item) {
            $this->fail(sprintf("There is no item entitled '%s'", $title));
        }

        return $item;
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
        // requests with this user between its creation and now; Karma changes.
        return $this->getUser($this->user->getUsername());
    }

    /**
     * Create a dummy user named $name with password $name.
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
            $title = sprintf(
                "%s %s", $this->faker->colorName, $this->faker->word
            );

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

        if ( ! empty($this->user)) {

            if (null != $this->password) {
                $password = $this->password;
            } else {
                $password = $this->user->getUsername();
            }

            $server['PHP_AUTH_USER'] = $this->user->getUsername();
            $server['PHP_AUTH_PW']   = $password;
        }

        // Set the desired output format, aka content-type
        // Not actually used by server it seems... Document!?
        //$server['CONTENT_TYPE'] = "application/json";
        // The server understands _format instead.
        // The server ignores that directive and returns json, unless there is
        // and error in which case Symfony kicks in and the error response is
        // returned in the specified _format.
        $parameters['_format'] = 'txt'; // for readable error responses

        $this->crawler = $this->client->request(
            $method, $uri, $parameters, $files,
            $server, $content, $changeHistory
        );

        return $this->crawler;
    }

    public function getJsonResponse() {
        $content = $this->client->getResponse()->getContent();
        return json_decode($content);
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

        if ( ! $this->client->getResponse()->isSuccessful()) {
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