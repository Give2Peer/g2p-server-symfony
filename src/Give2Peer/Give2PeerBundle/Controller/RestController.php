<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\ItemRepository;
use Give2Peer\Give2PeerBundle\Entity\TagRepository;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Entity\UserManager;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Routes are configured in YAML, in `Resources/config.routing.yml`.
 *
 * Class RestController
 * @package Give2Peer\Give2PeerBundle\Controller
 */
class RestController extends Controller
{
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
            return new ErrorJsonResponse("Username already taken", 001);
        }

        // Rebuke if too many Users created in 2 days from this IP
        // See http://php.net/manual/fr/dateinterval.construct.php
        $allowed = 42;
        $duration = new \DateInterval("P2D");
        $since = (new \DateTime())->sub($duration);
        $count = $um->countUsersCreatedBy($clientIp, $since);
        if ($count > $allowed) {
            return new ErrorJsonResponse("Too many registrations", 002, 429);
        }

        // Create a new User
        /** @var User $user */
        $user = $um->createUser();
        $user->setEmail('peer@give2peer.org');
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
        return $this->giveOrSpotAction($request, true);
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
        return $this->giveOrSpotAction($request, false);
    }

    /**
     * @param $itemId
     * @param Request $request
     * @return JsonResponse|ErrorJsonResponse
     */
    public function pictureUploadAction($itemId, Request $request)
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
            return new ErrorJsonResponse("Not authorized: no item.", 004);
        }

        if ($item->getGiver() != $user && $item->getSpotter() != $user) {
            return new ErrorJsonResponse("Not authorized.", 004);
        }

        // todo: move `web/pictures` to configuration
        $publicPath = $this->get('kernel')->getRootDir() . '/../web/pictures';
        $publicPath .= DIRECTORY_SEPARATOR . (string) $itemId;

        if (empty($request->files)) {
            return new ErrorJsonResponse("No `picture` file provided.", 003);
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('picture');

        if (null == $file) {
            return new ErrorJsonResponse("No `picture` file provided.", 003);
        }

        if (! $file->isValid()) {
            return new ErrorJsonResponse("Upload failed: ".$file->getErrorMessage(), 003);
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
            ), 003);
        }

        // Move the picture to a publicly available path
        try {
            $file->move($publicPath, '1.jpg');
        } catch (\Exception $e) {
            return new ErrorJsonResponse(sprintf(
                "Picture unrecognized : %s", $e->getMessage()), 003);
        }

        // Create a square thumbnail
        try {
            $this->makeSquareThumb(
                $publicPath . DIRECTORY_SEPARATOR . '1.jpg',
                $publicPath . DIRECTORY_SEPARATOR . 'thumb.jpg',
                200 // todo: move thumb size in pixels to configuration
            );
        } catch (\Exception $e) {
            return new ErrorJsonResponse(sprintf(
                "Thumbnail creation failed : %s", $e->getMessage()), 003);
        }

        $thumbUrl = join(DIRECTORY_SEPARATOR, [
            $request->getSchemeAndHttpHost(),
            'pictures',
            $itemId,
            'thumb.jpg',
        ]);
        $item->setThumbnail($thumbUrl);
//        $em->persist($item);
        $em->flush();

        return new JsonResponse($item);
    }

    function makeSquareThumb($source, $destination, $sideLength)
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


    public function tagsAction(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $repo = $em->getRepository('Give2PeerBundle:Tag');

        $tags = $repo->getTagNames();

        return new JsonResponse($tags);
    }

    /**
     * Returns a list of at most 32 Items, sorted by increasing distance to
     * the center of the circle.
     * You can skip the first `$skip` items if you already have them.
     *
     * The resulting JSON is an array of items that have the additional
     * `distance` property set up.
     *
     * @param float $latitude  Latitude of the center of the circle.
     * @param float $longitude Longitude of the center of the circle.
     * @param int   $skip      How many items to skip in the db query.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function findAroundCoordinatesAction($latitude, $longitude, $skip)
    {
        $maxResults = 32; // move this outta here

        // This sanitization may not be necessary anymore. Still.
        $latitude = floatval($latitude);
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
            return new ErrorJsonResponse('Db *must* be pgSQL.', 005, 500);
        }

        // Ask the repository to do the pgSQL-optimized query for us
        /** @var ItemRepository $repo */
        $repo = $em->getRepository('Give2PeerBundle:Item');
        $results = $repo->findAround($latitude, $longitude, $skip, $maxResults);

        return new JsonResponse($results);
    }



    //// UTILS /////////////////////////////////////////////////////////////////

    /**
     * Give an item whose properties are provided as POST variables.
     * Only the location property is mandatory.
     * There is no route acting on this directly, this is a helper.
     *
     * This creates an Item with the appropriate attributes, stores it and
     * sends it back as JSON.
     *
     * @param Request $request
     * @param bool $mine Is the item mine ?
     * @return JsonResponse
     */
    protected function giveOrSpotAction(Request $request, $mine)
    {
        /** @var SecurityContext $sc */
        $sc = $this->get('security.context');
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        // Recover the item data
        $location = $request->get('location');
        if (null == $location) {
            return new JsonResponse(["error"=>"No location provided."], 400);
        }
        $title = $request->get('title', '');
        $description = $request->get('description', '');
        $tagnames = $request->get('tags', []);

        // Fetch the Tags -- Ignore tags not found, for now.
        /** @var TagRepository $tagsRepo */
        $tagsRepo = $em->getRepository('Give2PeerBundle:Tag');
        $tags = $tagsRepo->findTags($tagnames);

        // Recover the user data
        $user = $sc->getToken()->getUser();

        // Create the item
        $item = new Item();
        $item->setLocation($location);
        try {
            $item->geolocate();
        } catch (\Exception $e) {
            $msg = sprintf("Cannot resolve geolocation: %s", $e->getMessage());
            return new ErrorJsonResponse($msg, 006);
        }
        $item->setTitle($title);
        $item->setDescription($description);
        foreach ($tags as $tag) {
            $item->addTag($tag);
        }
        if ($mine) {
            $item->setGiver($user);
        } else {
            $item->setSpotter($user);
        }

        // Add the item to database
        $em->persist($item);
        $em->flush();

        // Send the item as response
        return new JsonResponse($item);
    }



}
