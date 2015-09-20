<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Controller\ErrorCode as Error;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\ItemRepository;
use Give2Peer\Give2PeerBundle\Entity\TagRepository;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Entity\UserManager;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Give2Peer\Give2PeerBundle\Response\ExceededQuotaJsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Routes are configured in YAML, in `Resources/config/routing.yml`.
 *
 * Class RestController
 * @package Give2Peer\Give2PeerBundle\Controller
 */
class RestController extends Controller
{
    /** Number of items per level and per day a user may add */
    const ADD_ITEMS_PER_LEVEL = 2;
    /** Number of item queries per level and per day a user may make */
    const ITEM_QUERIES_PER_LEVEL = 20;

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        // todo: why not provide some documentation here ?
        return $this->render('Give2PeerBundle:Default:index.html.twig');
    }

    public function pingAction(Request $request)
    {
        return new JsonResponse("pong");
    }

    /**
     * Basic boring registration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerAction(Request $request)
    {
        /** @var SecurityContext $sc */
        //$sc = $this->get('security.context');
        /** @var EntityManager $em */
        //$em = $this->get('doctrine.orm.entity_manager');
        /** @var UserManager $um */
        $um = $this->get('fos_user.user_manager');

        // Recover the user data
        $username = $request->get('username');
        $password = $request->get('password');
        $email    = $request->get('email');
        $clientIp = $request->getClientIp();

        if (null == $username) {
            return new JsonResponse(["error"=>"No username provided."], 400);
        }
        if (null == $password) {
            return new JsonResponse(["error"=>"No password provided."], 400);
        }

        // Rebuke if username is taken
        $user = $um->findUserByUsername($username);
        if (null != $user) {
            return new ErrorJsonResponse(
                "Username already taken.", Error::UNAVAILABLE_USERNAME
            );
        }

        // Rebuke if email is taken
        $user = $um->findUserByEmail($email);
        if (null != $user) {
            return new ErrorJsonResponse(
                "Email already taken.", Error::UNAVAILABLE_EMAIL
            );
        }

        // Rebuke if too many Users created in 2 days from this IP
        // See http://php.net/manual/fr/dateinterval.construct.php
        $allowed = 42;
        $duration = new \DateInterval("P2D");
        $since = (new \DateTime())->sub($duration);
        $count = $um->countUsersCreatedBy($clientIp, $since);
        if ($count > $allowed) {
            return new ExceededQuotaJsonResponse("Too many registrations.");
        }

        // Create a new User
        /** @var User $user */
        $user = $um->createUser();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPlainPassword($password);
        $user->setCreatedBy($clientIp);
        $user->setEnabled(true);

        // This will canonicalize, encode, persist and flush
        $um->updateUser($user);

        // Send the user as response
        return new JsonResponse($user);
    }

    /**
     * A Giver is the legal owner of the item.
     *
     * Item attributes can be provided as POST variables :
     *   - location (mandatory)
     *   - title
     *   - description
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function giveAction(Request $request)
    {
        $request->attributes->set('gift', 'true');
        return $this->itemAdd($request);
    }

    /**
     * A Spotter does not own the Item, which is probably just lying around in
     * public space.
     *
     * See `give`.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function spotAction(Request $request)
    {
        $request->attributes->set('gift', 'false');
        return $this->itemAdd($request);
    }

    /**
     * Give an item whose properties are provided as POST variables.
     * Only the location property is mandatory.
     *
     * This creates an Item with the appropriate attributes, stores it and
     * sends it back as JSON, along with the experience gained.
     *
     * @param Request $request
     * @return JsonResponse
     */
    protected function itemAdd(Request $request)
    {
        /** @var SecurityContext $sc */
        $sc = $this->get('security.context');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var ItemRepository $itemRepo */
        $itemRepo = $em->getRepository('Give2PeerBundle:Item');

        // Recover the item data
        $location = $request->get('location');
        if (null == $location) {
            return new ErrorJsonResponse(
                "No location provided.", Error::BAD_LOCATION
            );
        }
        $title = $request->get('title', '');
        $description = $request->get('description', '');
        $tagnames = $request->get('tags', []);
        $gift = $request->get('gift', 'false') == 'true';

        // Fetch the Tags -- Ignore tags not found, for now.
        /** @var TagRepository $tagsRepo */
        $tagsRepo = $em->getRepository('Give2PeerBundle:Tag');
        $tags = $tagsRepo->findTags($tagnames);

        // Access the user data
        /** @var User $user */
        $user = $sc->getToken()->getUser();

        // Check whether the user exceeds his quotas or not
        $quota = self::ADD_ITEMS_PER_LEVEL * $user->getLevel();
        $duration = new \DateInterval("P1D"); // 24h
        $since = (new \DateTime())->sub($duration);
        $spent = $itemRepo->countItemsCreatedBy($user, $since);
        if ($spent >= $quota) {
            return new ExceededQuotaJsonResponse(
                "Created too many items today. Please wait and try again."
            );
        }

        // Create the item
        $item = new Item();
        $item->setLocation($location);
        try {
            $item->geolocate();
        } catch (\Exception $e) {
            $msg = sprintf("Cannot resolve geolocation: %s", $e->getMessage());
            return new ErrorJsonResponse($msg, Error::BAD_LOCATION);
        }
        $item->setTitle($title);
        $item->setDescription($description);
        foreach ($tags as $tag) {
            $item->addTag($tag);
        }
        if ($gift) {
            $item->setGiver($user);
        } else {
            $item->setSpotter($user);
        }

        // Add the item to database
        $em->persist($item);

        // Compute how much experience the user gains and then give it
        $experience = 3;
        if (! empty($item->getTitle()))  { $experience++; }
        if (0 < count($item->getTags())) { $experience++; }
        $user->addExperience($experience);

        // Flush the entity manager to save our changes
        $em->flush();

        // Send the item and other action data as response
        return new JsonResponse([
            'item'       => $item,
            'experience' => $experience,
        ]);
    }

    /**
     * @param $itemId
     * @param Request $request
     * @return JsonResponse|ErrorJsonResponse
     */
    public function itemPictureUploadAction($itemId, Request $request)
    {
        /** @var SecurityContext $sc */
        $sc = $this->get('security.context');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var ItemRepository $repo */
        $repo = $em->getRepository('Give2PeerBundle:Item');

        // Sanitize (this is *mandatory* !)
        $itemId = intval($itemId);

        // Recover the user data and check if we're the giver or the spotter
        // Later on we'll add authorization through spending NRG points.
        $user = $sc->getToken()->getUser();

        /** @var Item $item */
        $item = $repo->find($itemId);

        if (null == $item) {
            return new ErrorJsonResponse(
                "Not authorized: no item.", Error::NOT_AUTHORIZED
            );
        }

        if ($item->getGiver() != $user && $item->getSpotter() != $user) {
            return new ErrorJsonResponse(
                "Not authorized: not owner.", Error::NOT_AUTHORIZED
            );
        }

        // todo: move `web/pictures` to configuration
        $publicPath = $this->get('kernel')->getRootDir() . '/../web/pictures';
        $publicPath .= DIRECTORY_SEPARATOR . (string) $itemId;

        if (empty($request->files)) {
            return new ErrorJsonResponse(
                "No `picture` file provided.", Error::UNSUPPORTED_FILE
            );
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('picture');

        if (null == $file) {
            return new ErrorJsonResponse(
                "No `picture` file provided.", Error::UNSUPPORTED_FILE
            );
        }

        if (! $file->isValid()) {
            return new ErrorJsonResponse(
                "Upload failed: ".$file->getErrorMessage(),
                Error::UNSUPPORTED_FILE
            );
        }

        // Check extension
        // This should be improved later -- hi, you, future self ! Remember me ?
        // Remember to update the `makeSquareThumb` method, too !
        $allowedExtensions = [ 'jpg', 'jpeg' ];

        //$actualExtension = $file->getExtension(); // NO, tmp files have no extension
        $actualExtension = $file->getClientOriginalExtension();
        if (!in_array($actualExtension, $allowedExtensions)) {
            return new ErrorJsonResponse(sprintf(
                "Extension '%s' unsupported. Supported extensions : %s",
                $actualExtension, join(', ', $allowedExtensions)
            ), Error::UNSUPPORTED_FILE);
        }

        // Temp filename definition, since we only support one picture right now
        $filename = '1.jpg';

        // Move the picture to a publicly available path
        try {
            $file->move($publicPath, $filename);
        } catch (\Exception $e) {
            return new ErrorJsonResponse(
                sprintf("Picture unrecognized : %s", $e->getMessage()),
                Error::UNSUPPORTED_FILE
            );
        }

        // Create a square thumbnail
        try {
            $this->generateSquareThumb(
                $publicPath . DIRECTORY_SEPARATOR . $filename,
                $publicPath . DIRECTORY_SEPARATOR . 'thumb.jpg',
                200 // todo: move thumb size in pixels to configuration
            );
        } catch (\Exception $e) {
            return new ErrorJsonResponse(
                sprintf("Thumbnail creation failed : %s", $e->getMessage()),
                Error::UNSUPPORTED_FILE
            );
        }

        $thumbUrl = join(DIRECTORY_SEPARATOR, [
            $request->getSchemeAndHttpHost(),
            'pictures',
            $itemId,
            'thumb.jpg',
        ]);
        $item->setThumbnail($thumbUrl);
//        $em->persist($item); // unsure whether and why we'd need that
        $em->flush();

        return new JsonResponse($item);
    }

    function generateSquareThumb($source, $destination, $sideLength)
    {
        // Read the source image
        $sourceImage = imagecreatefromjpeg($source); // jpg only !
        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        $smallestSide = min($width, $height);

        $x = 0;
        $y = 0;
        if ($width < $height) {
            $y = floor(($height - $smallestSide) / 2);
        }
        else if ($width > $height) {
            $x = floor(($width - $smallestSide) / 2);
        }

        // Create a new, "virtual" image
        $virtualImage = imagecreatetruecolor($sideLength, $sideLength);

        // Then magic happens
        imagecopyresampled(
            $virtualImage, $sourceImage,
            0, 0, $x, $y,
            $sideLength, $sideLength,
            $smallestSide, $smallestSide
        );

        // Create the physical thumbnail image to its destination
        imagejpeg($virtualImage, $destination);
    }

    /**
     * Returns all available tags, as a JSONed array.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tagsAction(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var TagRepository $repo */
        $repo = $em->getRepository('Give2PeerBundle:Tag');

        $tags = $repo->getTagNames();

        return new JsonResponse($tags);
    }

    /**
     * Returns a list of at most 64 Items, sorted by increasing distance to
     * the center of the circle.
     * You can skip the first `$skip` items if you already have them.
     *
     * The resulting JSON is an array of items that have the additional
     * `distance` property set up.
     *
     * @param float $latitude Latitude of the center of the circle.
     * @param float $longitude Longitude of the center of the circle.
     * @param int $skip How many items to skip in the db query.
     * @param int|float $radius In meters, the max distance
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function findAroundCoordinatesAction($latitude, $longitude, $skip,
                                                $radius)
    {
        $maxResults = 64; // todo: move this to configuration

        // This sanitization may not be necessary anymore. Still.
        $latitude  = floatval($latitude);
        $longitude = floatval($longitude);

        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        $con = $em->getConnection();

        // Register our DISTANCE function, that only pgSQL can understand
        // Move this into a kernel hook ? or a more specific hook, maybe ?
        if ($con->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $em->getConfiguration()->addCustomNumericFunction(
                'DISTANCE',
                'Give2Peer\Give2PeerBundle\Query\AST\Functions\DistanceFunction'
            );
        } else {
            return new ErrorJsonResponse(
                'Database *must* be pgSQL.', Error::SYSTEM_ERROR, 500
            );
        }

        // Ask the repository to do the pgSQL-optimized query for us
        /** @var ItemRepository $repo */
        $repo = $em->getRepository('Give2PeerBundle:Item');
        $results = $repo->findAround(
            $latitude, $longitude, $skip, $radius, $maxResults
        );

        return new JsonResponse($results);
    }

}