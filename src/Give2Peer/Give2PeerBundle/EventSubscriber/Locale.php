<?php

namespace Give2Peer\Give2PeerBundle\EventSubscriber;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the 'Accept-Language' header in the request and sets the locale
 * accordingly.
 *
 * This bypasses the `_locale` attribute that can be set in routing.
 *
 * Class Locale
 * @package Give2Peer\Give2PeerBundle\EventSubscriber
 */
class Locale implements EventSubscriberInterface
{
    const PRIORITY = 15;

    private $defaultLocale;
    private $allowedLocales = ['fr', 'en']; // fixme: get these from config?

    public function __construct($defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $locale = $request->getPreferredLanguage($this->allowedLocales);

        if ($locale) $request->setLocale($locale);
    }

    public static function getSubscribedEvents()
    {
        return array(
            // must be registered right after the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', self::PRIORITY]],
        );
    }
}