<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\ItemRepository;
use Give2Peer\Give2PeerBundle\Entity\TagRepository;
use Give2Peer\Give2PeerBundle\Entity\ThankRepository;
use Give2Peer\Give2PeerBundle\Entity\UserManager;
use Give2Peer\Give2PeerBundle\Entity\UserRepository;
use Give2Peer\Give2PeerBundle\Service\Geocoder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Utilities, mostly sugar for the eyes and auto completion.
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
     * @return UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getEntityManager()->getRepository('Give2PeerBundle:User');
    }

    /**
     * @return ItemRepository
     */
    protected function getItemRepository()
    {
        return $this->getEntityManager()->getRepository('Give2PeerBundle:Item');
    }

    /**
     * @return TagRepository
     */
    protected function getTagRepository()
    {
        return $this->getEntityManager()->getRepository('Give2PeerBundle:Tag');
    }

    /**
     * @return ThankRepository
     */
    protected function getThankRepository()
    {
        return $this->getEntityManager()->getRepository('Give2PeerBundle:Thank');
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
     * @return null|Item
     */
    protected function getItem($id)
    {
        // Sanitize (this is *mandatory* !)
        $id = intval($id);

        return $this->getItemRepository()->find($id);
    }

    /**
     * Should ask a service instead of making an instance.
     * Should handle locale and region, too.
     *
     * @return Geocoder
     */
    protected function getGeocoder()
    {
        return new Geocoder();
    }
}