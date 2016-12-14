<?php

namespace Give2Peer\Give2PeerBundle\Service;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\ItemPicture;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * A Service that handles the item pictures.
 *
 * Responsibilities :
 * - create and inject the URLs of the image files (main and thumbs)
 * - create the actual files (main and thumbs) from the uploaded file
 * - delete the files when the ItemPicture entity is deleted (Doctrine hook)
 *
 * Note: Depends heavily on `php-gd` and is therefore one of the reasons why we
 *       have `ext-gd` listed as a dependency in our `composer.json`.
 */
class ItemPainter
{
    protected $requestStack;
    protected $dir_path;
    protected $url_path;
    protected $thumbs;
    private $extension;
    private $quality;

    public function __construct(RequestStack $requestStack, $dir_path, $url_path, $thumbs)
    {
        $this->requestStack = $requestStack;
        $this->dir_path = $dir_path;
        $this->url_path = $url_path;
        $this->thumbs   = $thumbs;

        $this->extension = 'jpg'; // change imagejpeg() calls if you change that
        $this->quality = 83;
    }

    /**
     * Inject the URLs of the pictures (and of their thumbnails) of the provided
     * $item, effectively painting it and completing the data it needs for
     * serialization.
     *
     * We need this because the URLs are generated from the server host and
     * configuration, and entities know nothing about these.
     *
     * @param Item $item
     */
    public function paintItem(Item $item)
    {
        foreach ($item->getPictures() as $picture) {
            $this->injectUrls($picture);
        }
    }

    /**
     * Inject the URL of the image and of the thumbnails into the $itemPicture.
     * We do not store URLs in the database, so we need to inject them into our
     * response using this method.
     *
     * @param ItemPicture $itemPicture
     * @return ItemPicture
     */
    public function injectUrls(ItemPicture $itemPicture)
    {
        $itemPicture->setUrl($this->getUrl($itemPicture));

        $thumbsUrls = [];

        foreach ($this->thumbs as $k=>$thumb) {
            $x = $thumb['x']; $y = $thumb['y'];
            $thumbsUrls["${x}x${y}"] = $this->getThumbUrl($itemPicture, $x, $y);
        }
        $itemPicture->setThumbnailsUrls($thumbsUrls);

        return $itemPicture;
    }

    /**
     * Create the image files (full-size ad thumbnails) from the uploaded $file
     * if we can. This leaves intact the uploaded $file, you'll need to delete
     * it by yourself.
     *
     * @param ItemPicture $itemPicture
     * @param UploadedFile $file
     * @throws \Exception
     */
    public function createFiles(ItemPicture $itemPicture, UploadedFile $file)
    {
        $subtype = strtolower($file->getClientOriginalExtension());

        $resource = $this->createImageResourceFromFilename(
            $file->getFileInfo()->getRealPath(), $subtype
        );

        $destination = $this->getFilePath($itemPicture);

        // Create the destination directory if it does not exist
        $destinationDir = substr(
            $destination, 0, strrpos($destination, DIRECTORY_SEPARATOR)
        );
        if (strlen($destinationDir) > 0) {
            // Maybe use the Filesystem Component here instead ?
            if ( ! is_dir($destinationDir)) {
                if ( ! mkdir($destinationDir, 0777, true)) {
                    throw new \Exception(
                        "Cannot create directory '$destinationDir'."
                    );
                }
            }
        }

        // Create the actual full-size image, converted to JPG
        // We might resize/crop the image if it's too big, in the future.
        imagejpeg($resource, $destination, $this->quality);

        // Create the thumbnails, they're JPG too
        foreach ($this->thumbs as $k=>$thumb) {
            $x = $thumb['x']; $y = $thumb['y'];
            $thumbDestination = $this->getThumbPath($itemPicture, $x, $y);
            $this->generateThumbnail($resource, $thumbDestination, $x, $y);
        }
    }

    /**
     * Delete the image files that were created by this picture.
     *
     * @param ItemPicture $picture
     * @throws \Exception
     */
    public function deleteFiles(ItemPicture $picture)
    {
        $id = $picture->getId();

        $main = $this->getFilePath($picture);
        if (is_file($main)) {
            if ( ! unlink($main)) {
                throw new \Exception(
                    "Unable to delete item picture #${id} " .
                    "main picture at '${main}'."
                );
            }
        }

        foreach ($this->thumbs as $thumb) {
            $x = $thumb['x']; $y = $thumb['y'];
            $thumbDestination = $this->getThumbPath($picture, $x, $y);
            if (is_file($thumbDestination)) {
                if ( ! unlink($thumbDestination)) {
                    throw new \Exception(
                        "Unable to delete item picture #${id} " .
                        "thumbnail at '${main}'."
                    );
                }
            }
        }
    }

    /**
     * Make sure that all Items retrieved from the database are painted with
     * the appropriate URLs.
     *
     * Note that, when using Doctrine\ORM\AbstractQuery#iterate(), postLoad
     * events will be executed immediately after objects are being hydrated,
     * and therefore associations are not guaranteed to be initialized.
     * It is not safe to combine usage of Doctrine\ORM\AbstractQuery#iterate()
     * and postLoad event handlers.
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof Item) {
            $this->paintItem($entity);
        }
    }

    /**
     * Make sure that all the pictures
     *
     * /!\
     * | This is not called for a DQL DELETE statement.
     *
     * Note that the postRemove event or any events triggered after an entity
     * removal can receive an uninitializable proxy in case you have configured
     * an entity to cascade remove relations. In this case, you should load
     * yourself the proxy in the associated pre event.
     * (we're using preRemove, it's simpler)
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof ItemPicture) {
            $this->deleteFiles($entity);
        }
    }

    /**
     * Supported image subtypes : jpg, jpeg, png, gif, and webp.
     * The support for webp is flaky at best.
     *
     * @param $filename
     * @param $subtype
     * @return resource
     * @throws \Exception
     */
    private function createImageResourceFromFilename($filename, $subtype)
    {
        $sourceImage = null;
        switch ($subtype) {
            case 'jpg';
            case 'jpeg';
                $sourceImage = imagecreatefromjpeg($filename);
                break;
            case 'png';
                $sourceImage = imagecreatefrompng($filename);
                break;
            case 'gif';
                $sourceImage = imagecreatefromgif($filename);
                break;
            case 'webp';
                // I have 'undefined function' in my IDE ?! ; it works, though. Kinda.
                $sourceImage = imagecreatefromwebp($filename);
                break;
            default:
                throw new \Exception("Unsupported image subtype: '$subtype'.");

        }

        // Sanity check that should never fail, but you never know...
        if (null == $sourceImage) {
            throw new \Exception(
                "Failed to create an image resource " .
                "for file '$filename' of subtype '$subtype'."
            );
        }

        return $sourceImage;
    }

    /**
     * Generate a JPG thumbnail $fromResource image.
     * It will take the biggest rectangle that fits in the center of the image.
     *
     * Note: transparency will be cast to black.
     *
     * @param $fromResource
     * @param $destination
     * @param $x
     * @param $y
     */
    private function generateThumbnail($fromResource, $destination, $x, $y)
    {
        $width  = imagesx($fromResource);
        $height = imagesy($fromResource);

        $smallestSide = min($width, $height);

        $originX = 0; $originY = 0;
        if ($width < $height) {
            $originY = floor(($height - $smallestSide) / 2);
        }
        else if ($width > $height) {
            $originX = floor(($width - $smallestSide) / 2);
        }

        $virtualImage = imagecreatetruecolor($x, $y);
        imagecopyresampled(
            $virtualImage, $fromResource,
            0, 0, $originX, $originY, $x, $y,
            $smallestSide, $smallestSide
        );
        imagejpeg($virtualImage, $destination);
    }

    private function getFilePath(ItemPicture $itemPicture)
    {
        return $this->dir_path . DIRECTORY_SEPARATOR .
        $itemPicture->getId() . '.' . $this->extension;
    }

    private function getThumbPath(ItemPicture $itemPicture, $x, $y)
    {
        return $this->dir_path . DIRECTORY_SEPARATOR .
        $itemPicture->getId() . "_${x}x${y}." . $this->extension;
    }

    private function getUrl(ItemPicture $itemPicture)
    {
        return join('/', [
            $this->getRequest()->getSchemeAndHttpHost(),
            $this->url_path,
            $itemPicture->getId() . '.' . $this->extension,
        ]);
    }

    private function getThumbUrl(ItemPicture $itemPicture, $x, $y)
    {
        return join('/', [
            $this->getRequest()->getSchemeAndHttpHost(),
            $this->url_path,
            $itemPicture->getId() . "_${x}x${y}." . $this->extension,
        ]);
    }

    private function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }
}