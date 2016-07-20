<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Give2Peer\Give2PeerBundle\Service\Thumbnailer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc; // don't remove ; used as annotation in comments
use Give2Peer\Give2PeerBundle\Controller\ErrorCode as Error;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Give2Peer\Give2PeerBundle\Response\ExceededQuotaJsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Item CRUD, with level authorization, and item picture upload too.
 *
 * Routes are configured in YAML, in `Resources/config/routing.yml`.
 * ApiDoc's documentation can be found at :
 * https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.md
 */
class ItemController extends BaseController
{

    /**
     * Publish a new item.
     *
     * Only the `location` of the item is mandatory.
     *
     * #### Restrictions
     *
     * Any user can publish items, but one is subjected to daily quotas.
     *
     * `Daily Quota = 2 * ( User Level + 1 )`
     *
     * #### Effects
     *
     * This creates an item with the appropriate attributes,
     * stores it and sends it back as JSON, along with the karma gained.
     *
     * #### Features
     *
     *   - [giving_items.feature](https://github.com/Give2Peer/g2p-server-symfony/blob/master/features/giving_items.feature)
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
     *     {
     *       "name"="type", "dataType"="string", "required"=false,
     *       "description"="Either 'gift', 'lost', or the default 'moop'."
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
        $type = $request->get('type', Item::TYPE_MOOP);
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
        try {
            $item->setType($type);
        } catch (\InvalidArgumentException $e) {
            $msg = sprintf("Cannot set type: %s", $e->getMessage());
            return new ErrorJsonResponse($msg, Error::BAD_ITEM_TYPE);
        }
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
        // Not needed because item is owning side of relationship. Good design ?
        // $em->persist($user);

        // Compute how much karma the user gains and then give it
        // 3 points for giving, plus one point for a title, and one for tags.
        $karma = 3;
        if (2 < mb_strlen($item->getTitle())) { $karma++; }
        if (0 < count($item->getTags()))      { $karma++; }
        $user->addKarma($karma);

        // Flush the entity manager to commit our changes to database
        $em->flush();

        // Send the item and other action data as response
        return $this->respond([
            'item'  => $item,
            'karma' => $karma,
        ]);
    }

    /**
     * Delete the item `id`.
     *
     * #### Restrictions
     *
     * This only works if you're the author of that item.
     * There might be more complex deletion privileges in the future.
     *
     * #### Effects
     *
     * This marks the item as deleted, it does not immediately `DELETE` the item from the database.
     * A maintenance (CRON) task will handle the actual deletion of stale soft-deleted items.
     * The pictures associated to that item will be deleted as well by the maintenance (CRON) task.
     *
     * #### Features
     *
     *   - [deleting_items.feature](https://github.com/Give2Peer/g2p-server-symfony/blob/master/features/deleting_items.feature)
     *
     *
     * @ApiDoc()
     *
     * @param Request $request
     * @param int $id Identifier of the item to delete
     * @return ErrorJsonResponse|JsonResponse
     */
    public function itemDeleteAction(Request $request, $id)
    {
        $user = $this->getUser();
        $item = $this->getItem($id);

        if (null == $item) {
            return new ErrorJsonResponse(
                "Item #$id does not exist.", Error::NOT_AUTHORIZED
            );
        }

        if ($item->getAuthor() != $user) {
            return new ErrorJsonResponse(
                "Not author of item #$id.", Error::NOT_AUTHORIZED
            );
        }

        $item->markAsDeleted();

        $this->getEntityManager()->flush();

        return $this->respond([
            'item' => $item
        ]);
    }

    /**
     * Upload and attach a picture to the item `id`.
     *
     * #### Restrictions
     *
     * You need to be the author of that item. This may evolve into a more complex rule in the future.
     *
     * #### Supports
     *
     *   - `JPG`, `JPEG`
     *   - `PNG`
     *   - `GIF`
     *   - `WebP`
     *
     * #### Features
     *
     *   - [picturing_items.feature](https://github.com/Give2Peer/g2p-server-symfony/blob/master/features/picturing_items.feature)
     *
     * #### Ideas
     *
     *   - Allow uploading more than one picture for one item, since level ???
     *   - Allow uploading pictures for others' items, since level ???
     *
     *
     * @ApiDoc(
     *   parameters = {
     *     {
     *       "name"="picture", "dataType"="file",
     *       "required"=true, "format"="jpg|png|gif|webp",
     *       "description"="Picture file to attach to the item."
     *     }
     *   }
     * )
     * @param  Request  $request
     * @param  int      $id      Id of the Item to upload the picture for.
     * @return Response
     */
    public function itemPictureUploadAction(Request $request, $id)
    {
        // Recover the user data and check if we're the giver or the spotter.
        // Later on we'll add authorization through karma levels ?
        $user = $this->getUser();
        $item = $this->getItem($id);

        if (null == $item) {
            return new ErrorJsonResponse(
                "Item #$id does not exist.", Error::NOT_AUTHORIZED
            );
        }

        if ($item->getAuthor() != $user) {
            return new ErrorJsonResponse(
                "You are not the owner of item #$id.", Error::NOT_AUTHORIZED
            );
        }
        
        // We have different configurations for prod and test environments.
        $publicPath = $this->getParameter('give2peer.pictures.directory');
        $publicPath .= DIRECTORY_SEPARATOR . (string) $item->getId();

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

        if ( ! $file->isValid()) {
            return new ErrorJsonResponse(
                "Upload failed: ".$file->getErrorMessage(),
                Error::UNSUPPORTED_FILE
            );
        }

        // Check extension
        // Note that mime-types can be forged too, so extension is good enough.
        // This may be improved further -- hi, you, future self ! Remember me ?
        // Remember to update the `generateSquareThumb` method, too !
        // Later: I remember you, past me, I remember. I <3 u, my only friend !
        $allowedExtensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];

        //$actualExtension = $file->getExtension(); // NO, tmp files have no ext
        $actualExtension = strtolower($file->getClientOriginalExtension());
        if ( ! in_array($actualExtension, $allowedExtensions)) {
            return new ErrorJsonResponse(sprintf(
                "Extension '%s' unsupported. Supported extensions : %s",
                $actualExtension, join(', ', $allowedExtensions)
            ), Error::UNSUPPORTED_FILE);
        }

        // Temp filename definition, since we only support one picture right now
        $filename = "1.$actualExtension";

        // Move the picture to a publicly available path
        try {
            $file->move($publicPath, $filename);
        } catch (\Exception $e) {
            return new ErrorJsonResponse(
                sprintf("Failed to copy the picture : %s", $e->getMessage()),
                Error::UNSUPPORTED_FILE
            );
        }

        // Create a square thumbnail
        try {
            $thumbSize = $this->getParameter('give2peer.pictures.size');
            Thumbnailer::generateSquare(
                $publicPath . DIRECTORY_SEPARATOR . $filename,
                $publicPath . DIRECTORY_SEPARATOR . 'thumb.jpg',
                $thumbSize, $actualExtension
            );
        } catch (\Exception $e) {
            // As the mime-type and/or extension can be forged, we use EAFP on
            // the thumbnail generation, so we need to delete the uploaded file.
            // I'm not sure how secure imagecreatefrom*** functions are, though.
            unlink($publicPath . DIRECTORY_SEPARATOR . $filename);
            return new ErrorJsonResponse(
                sprintf("Thumbnail creation failed : %s", $e->getMessage()),
                Error::UNSUPPORTED_FILE
            );
        }

        // Generate the thumbnail absolute URL and save it.
        // Note: this does not depend on configuration ; probably should.
        $thumbUrl = join(DIRECTORY_SEPARATOR, [
            $request->getSchemeAndHttpHost(),
            'pictures',
            $item->getId(),
            'thumb.jpg',
        ]);
        $item->setThumbnail($thumbUrl);
        
        // Flush our changes to the item into the database
        $this->getEntityManager()->flush();

        return $this->respond([
            'item' => $item
        ]);
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

        $items = $this->findAround(
            $latitude, $longitude, $maxDistance, $skip
        );

        // Gain the daily karma point if not done already today
        /** @var User $user (but may not be if firewall goes away) */
        $user = $this->getUser();
        if ( ! $user->hadDailyKarmaPoint()) {
            $user->addDailyKarmaPoint();
        }
        $this->getEntityManager()->flush();

        return $this->respond(['items'=>$items]);
    }

    /**
     * THIS (probably) BELONGS IN THE ITEM REPOSITORY
     *
     * Find items by increasing distance to the specified coordinates.
     *
     * Return a list of at most %give2peer.items.max_per_page% Items, sorted
     * by increasing distance to location described by $latitude, $longitude,
     * up to $maxDist (in meters) if nonzero.
     *
     * center of the circle described by $latitude, $longitude, and $radius.
     * Note that the $radius is curved along the great circles of Earth.
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
     * @param float $maxDist   In meters, the max distance. Provide 0 to ignore.
     *
     * @return ErrorJsonResponse|Item[] Items matching the query, enhanced with their `distance`.
     */
    public function findAround($latitude, $longitude, $maxDist, $skip)
    {
        // We've got to paginate the results !
        $maxResults = $this->getParameter('give2peer.items.max_per_page');

        // This sanitization may not be necessary anymore. Still.
        $latitude  = floatval($latitude);
        $longitude = floatval($longitude);

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $conf = $em->getConfiguration();
        $conn = $em->getConnection();

        // Register our DISTANCE function, that only pgSQL can understand.
        // Move this into a kernel hook ? or a more specific hook, maybe ?
        // Meh. No. Just move it to its own method when we'll need to.
        // Advisor pattern ? Hmm.
        if ($conn->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            // We need to be ABSOLUTELY SURE we don't do this twice !
            if (null == $conf->getCustomNumericFunction('DISTANCE')) {
                $conf->addCustomNumericFunction(
                    'DISTANCE',
                    'Give2Peer\Give2PeerBundle\Query\AST\Functions\DistanceFunction'
                );
            } else {
                // Do not hesitate to remove this, it's just a ~sanity check.
                // Actually, if this happens, you've probably been naughty.
                return new ErrorJsonResponse(
                    'Ran findAroundCoordinates twice', Error::SYSTEM_ERROR, 500
                );
            }
        } else {
            return new ErrorJsonResponse(
                'Database MUST be pgSQL.', Error::SYSTEM_ERROR, 500
            );
        }

        // Ask the item repository to execute the pgSQL-optimized query for us.
        $repo = $this->getItemRepository();
        $results = $repo->findAround(
            $latitude, $longitude, $skip, $maxDist, $maxResults
        );

        return $results;
    }

}