<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Give2Peer\Give2PeerBundle\Controller\ErrorCode as Error;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Give2Peer\Give2PeerBundle\Response\ExceededQuotaJsonResponse;

/**
 * todo:
 * - continue moving methods out of this file and into controllers in `Rest/`.
 * 
 * Routes are configured in YAML, in `Resources/config/routing.yml`.
 * ApiDoc's documentation can be found at :
 * https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.md
 *
 * Class RestController
 * @package Give2Peer\Give2PeerBundle\Controller
 */
class RestController extends BaseController
{

    /** Number of item queries per day a user may make at level 0 */
    const ITEM_QUERIES_LEVEL_0 = 30;
    /** Number of item queries per level and per day a user may make */
    const ITEM_QUERIES_PER_LEVEL = 15;

    /**
     * Provide some generated documentation about this REST API.
     */
    public function indexAction()
    {
        return $this->forward('NelmioApiDocBundle:ApiDoc:index');
    }

    /**
     * This exists because sometimes clients want to check their connectivity
     * and credentials by "pinging" the server.
     * 
     * This should return useful information about the server.
     *
     * @return JsonResponse
     */
    public function pingAction()
    {
        return new JsonResponse("pong");
    }

    /**
     * Get the profile information of the current or specified user.
     * 
     * If `username` is provided, will look for the public profile of that user.
     *
     * @ApiDoc(
     *   parameters = {
     *     {
     *       "name"="username", "dataType"="string", "required"=false,
     *       "description"="Example: -2.4213, 43.1235"
     *     },
     *   }
     * )
     * @return ErrorJsonResponse|JsonResponse
     */
    public function profileAction (Request $request)
    {
        $username = $request->get('username');
        if (null != $username) {
            return $this->publicProfileAction($request);
        } else {
            return $this->privateProfileAction($request);
        }
    }

    /**
     * Get the (private) profile information of the current user.
     *
     * @param  Request $request
     * @return ErrorJsonResponse|JsonResponse
     */
    public function privateProfileAction (Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (empty($user)) {
            return new ErrorJsonResponse("Nope.", Error::NOT_AUTHORIZED);
        }

        /** @var PersistentCollection $items */
        $items = $user->getItemsAuthored();

        return new JsonResponse([
            'user'  => $user,
            //'items' => $items, // /!\ PITFALL /!\ : parser thinks it's empty
            'items' => $items->getValues(),
        ]);
    }

    /**
     * Get the (public) profile information of the given user.
     *
     * @param  Request $request
     * @return ErrorJsonResponse|JsonResponse
     */
    public function publicProfileAction (Request $request)
    {
        $um = $this->getUserManager();
        $username = $request->get('username');
        
        if (null == $username) {
            return new ErrorJsonResponse("Bad username.", Error::BAD_USERNAME);
        }
        
        /** @var User $user */
        $user = $um->findUserByUsername($username);

        if (empty($user)) {
            return new ErrorJsonResponse("Bad username.", Error::BAD_USERNAME);
        }

        return new JsonResponse([
            'user' => $user->publicJsonSerialize(),
        ]);
    }

    /**
     * A Giver is the legal owner of the item.
     *
     * Item attributes can be provided as POST variables :
     *   - location (mandatory)
     *   - title
     *   - description
     *
     * @deprecated
     * @param Request $request
     * @return JsonResponse
     */
//    public function giveAction(Request $request)
//    {
//        $request->attributes->set('gift', 'true');
//        return $this->itemAddAction($request);
//    }

    /**
     * A Spotter does not own the Item, which is probably just lying around in
     * public space.
     *
     * See `give`.
     *
     * @deprecated
     * @param Request $request
     * @return JsonResponse
     */
//    public function spotAction(Request $request)
//    {
//        $request->attributes->set('gift', 'false');
//        return $this->itemAddAction($request);
//    }

    /**
     * Publish a new item.
     *
     * Only the `location` of the item is mandatory.
     *
     * This creates an Item with the appropriate attributes, stores it and
     * sends it back as JSON, along with the experience gained.
     *
     * @ApiDoc(
     *   parameters = {
     *     {
     *       "name"="location", "dataType"="string", "required"=true,
     *       "format"="{latitude}, {longitude}",
     *       "description"="Example: -2.4213, 43.1235"
     *     },
     *     {
     *       "name"="title", "dataType"="string", "required"=false,
     *       "description"="UTF-8 truncated to 32 characters."
     *     },
     *   }
     * )
     * @param  Request $request
     * @return JsonResponse
     */
    public function itemAddAction(Request $request)
    {
        $em = $this->getEntityManager();
        $itemRepo = $this->getItemRepository();
        $tagsRepo = $this->getTagRepository();

        // Recover the item data
        $location = $request->get('location');
        if (null == $location) {
            return new ErrorJsonResponse(
                "No location provided.", Error::BAD_LOCATION
            );
        }
        // Note: some title sanitization happens in `Item::setTitle`
        $title = $request->get('title', '');
        $description = $request->get('description', '');
        $tagnames = $request->get('tags', []);

        // Fetch the Tags -- Ignore tags not found, for now.
        $tags = $tagsRepo->findTags($tagnames);

        // Access the user data
        /** @var User $user */
        $user = $this->getUser();

        // Check whether the user exceeds his quotas or not
        $quota_left = $itemRepo->getAddItemsCurrentQuota($user);
        if ($quota_left < 1) {
            return new ExceededQuotaJsonResponse();
        }

        // Check whether the location can be geocoded or not
        $g = $this->getGeocoder();
        try {
            $coordinates = $g->getCoordinates($location);
        } catch (\Exception $e) {
            $msg = sprintf("Cannot resolve location: %s", $e->getMessage());
            return new ErrorJsonResponse($msg, Error::BAD_LOCATION);
        }

        // Create the item
        $item = new Item();
        $item->setLocation($location);
        $item->setLatitude(floatval($coordinates[0]));
        $item->setLongitude(floatval($coordinates[1]));
        $item->setTitle($title);
        $item->setDescription($description);
        foreach ($tags as $tag) {
            $item->addTag($tag);
        }

        $item->setAuthor($user);
        // I'm pretty confident this is not mandatory as it is the inverse side
        // of the bidirectional relationship, BUT it IS good design.
        $user->addItemAuthored($item);

        // Add the item to database
        $em->persist($item);

        // fixme: test
//        $em->persist($user);

        // Compute how much karma the user gains and then give it
        // 3 points for giving, plus one point for a title, and one for tags.
        $karma = 3;
        if (2 < mb_strlen($item->getTitle())) { $karma++; }
        if (0 < count($item->getTags()))      { $karma++; }
        $user->addKarma($karma);

        // Flush the entity manager to commit our changes to database
        $em->flush();

        // Send the item and other action data as response
        return new JsonResponse([
            'item'  => $item,
            'karma' => $karma,
        ]);
    }

    /**
     *
     *
     * @param Request $request
     * @param $id
     * @return ErrorJsonResponse|JsonResponse
     */
    public function itemDeleteAction(Request $request, $id)
    {
        $user = $this->getUser();
        $item = $this->getItem($id);

        if (null == $item) {
            return new ErrorJsonResponse(
                "Not authorized: no item.", Error::NOT_AUTHORIZED
            );
        }

        if ($item->getAuthor() != $user) {
            return new ErrorJsonResponse(
                "Not authorized: not owner.", Error::NOT_AUTHORIZED
            );
        }

        $item->markAsDeleted();

        $this->getEntityManager()->flush();

        return new JsonResponse(['item'=>$item]);
    }

    /**
     * Upload a picture for the item `id`.
     * 
     * You need to be the author of the item.
     * 
     * Ideas :
     * - Allow uploading photos for others' items, since level ?
     *
     * @ApiDoc(
     *   parameters = {
     *     {
     *       "name"="picture", "dataType"="file",
     *       "required"=true, "format"="jpg",
     *       "description"="Picture file to attach to the item. JPG only for now."
     *     }
     *   }
     * )
     * @param Request $request
     * @param int     $id      Id of the item to upload the picture for.
     * @return JsonResponse|ErrorJsonResponse
     */
    public function itemPictureUploadAction(Request $request, $id)
    {
        // Recover the user data and check if we're the giver or the spotter
        // Later on we'll add authorization through spending NRG points.
        $user = $this->getUser();

        $item = $this->getItem($id);

        if (null == $item) {
            return new ErrorJsonResponse(
                "Not authorized: no item.", Error::NOT_AUTHORIZED
            );
        }

        if ($item->getAuthor() != $user) {
            return new ErrorJsonResponse(
                "Not authorized: not owner.", Error::NOT_AUTHORIZED
            );
        }
        
        // todo: move `web/pictures` to configuration
        $publicPath = $this->get('kernel')->getRootDir() . '/../web/pictures';
        $publicPath .= DIRECTORY_SEPARATOR . (string) intval($id);

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
            $id,
            'thumb.jpg',
        ]);
        $item->setThumbnail($thumbUrl);
        
        // Flush our changes to the item to the database
        $this->getEntityManager()->flush();

        return new JsonResponse(['item'=>$item]);
    }

    /**
     * Should probably be moved to a thumb generation service.
     *
     * @param $source
     * @param $destination
     * @param $sideLength
     */
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
     * Get items sorted by increasing distance from a location.
     *
     * The location is described by its `latitude` and `longitude`,
     * which are simple floating point numbers. No fancy ISO 6709 for now.
     *
     * You can skip the first `skip` items if you already have them.
     * You can also make sure that no items further than `maxDistance` meters
     * away from the location are returned.
     *
     * The resulting JSON is an array of items that have the additional
     * `distance` property, which is their distance in meters to the location,
     * for convenience.
     *
     * Note that the distances are computed along the great circles of Earth.
     *
     * @ApiDoc(
     *   parameters = {
     *     {
     *       "name"="maxDistance", "dataType"="float", "required"=false, "default"=0,
     *       "description"="Ignore items further than `maxDistance` meters away from the location. A value of zero will be ignored."
     *     },
     *     {
     *       "name"="skip", "dataType"="int", "required"=false, "default"=0,
     *       "description"="Skip the first `skip` items in the query. Useful for pagination, as the query is limited to a maximum of 64 results."
     *     },
     *   }
     * )
     * @param Request $request
     * @param float $latitude  The latitude between -90 and 90.
     * @param float $longitude The longitude between -180 and 180.
     * @return JsonResponse
     */
    public function itemsAroundAction(Request $request, $latitude, $longitude)
    {
        // Filter around a location
//        $around = $request->get('location', null);
//        $latitude = null; $longitude = null;
//        if (null != $around) {
//            $g = $this->getGeocoder();
//            try {
//                $coordinates = $g->getCoordinates($around);
//            } catch (\Exception $e) {
//                $msg = sprintf("Cannot resolve location: %s", $e->getMessage());
//                return new ErrorJsonResponse($msg, Error::BAD_LOCATION);
//            }
//            $latitude  = $coordinates[0];
//            $longitude = $coordinates[1];
//        }

        // Filter by maximum distance to location
        $maxDistance = $request->get('maxDistance', null);
        if (null != $maxDistance) {
            $maxDistance = abs(floatval($maxDistance)); // lazy ☠
        }

        // Filter by skipping the fist results
        $skip = $request->get('skip', null);
        if (null != $skip) {
            $skip = abs(intval($skip)); // lazy ☠
        }

        $items = $this->findAroundCoordinates(
            $latitude, $longitude, $maxDistance, $skip
        );

        return new JsonResponse($items);
    }

    /**
     * Find items by increasing distance to the specified coordinates.
     *
     * Return a list of at most 64 Items, sorted by increasing distance to the
     * center of the circle described by `latitude`, `longitude`, and `radius`.
     * Note that the `radius` is curved along the great circles of Earth.
     *
     * You can skip the first `skip` items if you already have them.
     *
     * The result is an array of items that have the additional
     * `distance` property, which is their distance in meters to the center of
     * the circle, for convenience.
     *
     * @param float $latitude  Latitude of the center of the circle, between -90 and 90.
     * @param float $longitude Longitude of the center of the circle, between -180 and 180.
     * @param int   $skip      How many items to skip in the query.
     * @param float $radius    In meters, the max distance. Provide 0 to ignore.
     *
     * @return Item[] Items matching the query, enhanced with their `distance`.
     */
    public function findAroundCoordinates($latitude, $longitude, $radius, $skip)
    {
        $maxResults = 64; // todo: move this to configuration

        // This sanitization may not be necessary anymore. Still.
        $latitude  = floatval($latitude);
        $longitude = floatval($longitude);

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $conf = $em->getConfiguration();
        $conn = $em->getConnection();

        // Register our DISTANCE function, that only pgSQL can understand
        // Move this into a kernel hook ? or a more specific hook, maybe ?
        if ($conn->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            // We need to be ABSOLUTELY SURE we don't do this twice !
            if (null == $conf->getCustomNumericFunction('DISTANCE')) {
                $conf->addCustomNumericFunction(
                    'DISTANCE',
                    'Give2Peer\Give2PeerBundle\Query\AST\Functions\DistanceFunction'
                );
            } else {
                // Do not hesitate to remove this, it's just a ~sanity check.
                return new ErrorJsonResponse(
                    'Ran findAroundCoordinates twice', Error::SYSTEM_ERROR, 500
                );
            }
        } else {
            return new ErrorJsonResponse(
                'Database MUST be pgSQL.', Error::SYSTEM_ERROR, 500
            );
        }

        // Ask the repository to do the pgSQL-optimized query for us
        $repo = $this->getItemRepository();
        $results = $repo->findAround(
            $latitude, $longitude, $skip, $radius, $maxResults
        );

        return $results;
    }

}