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
 * This will not be a problem for the API but if we handle regular web pages...
 * Just filter using the `api/` prefix in the URL of the request ; get to work !
 *
 * Class Locale
 * @package Give2Peer\Give2PeerBundle\EventSubscriber
 */
class Locale implements EventSubscriberInterface
{
    const PRIORITY = 15; // why are other listeners not using constants ? Hmmm.

    private $defaultLocale;
    private $allowedLocales;

    public function __construct($defaultLocale = 'en', $allowedLocales = ['en'])
    {
        $this->defaultLocale  = $defaultLocale;
        $this->allowedLocales = $allowedLocales;
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