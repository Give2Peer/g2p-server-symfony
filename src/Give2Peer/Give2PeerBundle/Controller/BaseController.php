<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\ItemPictureRepository;
use Give2Peer\Give2PeerBundle\Entity\ItemRepository;
use Give2Peer\Give2PeerBundle\Entity\ReportRepository;
use Give2Peer\Give2PeerBundle\Entity\TagRepository;
use Give2Peer\Give2PeerBundle\Entity\ThankRepository;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Entity\UserManager;
use Give2Peer\Give2PeerBundle\Entity\UserRepository;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Give2Peer\Give2PeerBundle\Service\Geocoder;
use Give2Peer\Give2PeerBundle\Service\ItemPainter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Utilities, mostly sugar for the eyes and for auto completion.
 */
abstract class BaseController extends Controller
{
    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->get('doctrine.orm.entity_manager');
    }
    
    /**
     * @return EntityManager
     */
    protected function getEm()
    {
        return $this->getEntityManager();
    }

    /**
     * @return UserManager
     */
    protected function getUserManager()
    {
        return $this->get('fos_user.user_manager');
    }

    /**
     * @deprecated
     * @return SecurityContext
     */
    protected function getSecurityContext()
    {
        return $this->get('security.context');
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }

    /**
     * @return ItemPainter
     */
    protected function getItemPainter()
    {
        return $this->get('g2p.item_painter');
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getEm()->getRepository('Give2PeerBundle:User');
    }

    /**
     * @return ItemRepository
     */
    protected function getItemRepository()
    {
        return $this->getEm()->getRepository('Give2PeerBundle:Item');
    }

    /**
     * @return ItemPictureRepository
     */
    protected function getItemPictureRepository()
    {
        return $this->getEm()->getRepository('Give2PeerBundle:ItemPicture');
    }

    /**
     * @return TagRepository
     */
    protected function getTagRepository()
    {
        return $this->getEm()->getRepository('Give2PeerBundle:Tag');
    }

    /**
     * @return ThankRepository
     */
    protected function getThankRepository()
    {
        return $this->getEm()->getRepository('Give2PeerBundle:Thank');
    }

    /**
     * @return ReportRepository
     */
    protected function getReportRepository()
    {
        return $this->getEm()->getRepository('Give2PeerBundle:Report');
    }

    /**
     * Sugary extra layer to find a user by its identifier $id.
     * Silently fails and returns `null` if the user was not found.
     * 
     * @param $id
     * @return \FOS\UserBundle\Model\UserInterface|User|null
     */
    protected function getUserById($id)
    {
        return $this->getUserManager()->findUserBy(['id' => $id]);
    }

    /**
     * Sugary extra layer to `find()` and sanitize input `$id`.
     * 
     * Return `null` when no item by that id could be found.
     * 
     * We could use type converters and not this method, but that way we can
     * more easily and more finely control the 404 error response.
     * If you can figure out how to finely control the error responses, use type
     * converters instead !
     *
     * @param $id
     * @return Item|null
     */
    protected function getItem($id)
    {
        // Sanitize (this is *mandatory* !)
        $id = intval($id);

        $item = $this->getItemRepository()->find($id);

        return $item;
    }

    /**
     * Sugary extra layer to `find()` and sanitize input `$id`,
     * and disable the soft-deletion filter.
     *
     * Return `null` when no item by that id could be found.
     *
     * We could use type converters and not this method, but that way we can
     * more easily and more finely control the 404 error response.
     * If you can figure out how to finely control the error responses, use type
     * converters instead !
     *
     * @param $id
     * @return Item|null
     */
    protected function getItemIncludingDeleted($id)
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('softdeleteable');
        $item = $this->getItem($id);
        $filters->enable('softdeleteable');

        return $item;
    }

    /**
     * Should ask a service instead of making an instance.
     * Should handle locale and region, too, see above. ↑
     *
     * @return Geocoder
     */
    protected function getGeocoder()
    {
        // return $this->get('g2p.geocoder') // or a third party one if we can bridge ?
        return new Geocoder();
    }


    /**
     * For future support of XML and text Responses.
     * Something with the _format argument.
     *
     * @param $contents
     * @param int $status
     * @param array $headers
     * @return JsonResponse|Response
     */
    protected function respond($contents, $status=Response::HTTP_OK, $headers=[])
    {
        return new JsonResponse($contents, $status, $headers);
    }

    /**
     * For future support of XML and text Responses.
     * Something with the _format argument.
     *
     * @param string $msg
     * @param array $parameters for the translation
     * @param int $status HTTP status code
     * @return ErrorJsonResponse|Response
     */
    protected function error($msg, $parameters=[], $status=Response::HTTP_BAD_REQUEST)
    {
        $msg = 'api.error.' . $msg; // implicit voodoo ; sorry, I'm lazy.
        return new ErrorJsonResponse(
            $this->getTranslator()->trans($msg, $parameters), $msg, $status
        );
    }
}