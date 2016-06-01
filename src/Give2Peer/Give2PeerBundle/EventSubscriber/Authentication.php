<?php

namespace Give2Peer\Give2PeerBundle\EventSubscriber;

use FOS\UserBundle\Model\UserManager;
use Give2Peer\Give2PeerBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * 
 * 
 * 
 * THIS IS NOT USED RIGHT NOW.
 * 
 * 
 *
 * Hooks the authentication process to try to authenticate the User from the
 * name/restToken pair if he provided it. If they're invalid, 501.
 *
 * This'll be probably rewritten in the future, as Symfony evolves.
 * 
 * We changed our strategy and made a very smart registration API instead.
 * 
 * I keep this as a snippet for future kernel event hooking usage lookup.
 */
class Authentication implements EventSubscriberInterface
{
    protected $tokenStorage;
    protected $userManager;

    function __construct(TokenStorage $tokenStorage, UserManager $userManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userManager = $userManager;
    }

    static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('authOrRegister', 0),
//            FOSUserEvents::REGISTRATION_COMPLETED => array('complete', 0),
        );
    }


    //// EVENT LISTENERS ///////////////////////////////////////////////////////

    public function authOrRegister(GetResponseEvent $e)
    {

        /**
         * YES WE'RE NOT DOING ANYTHING HERE FOR NOW
         */

//        print('authenticate?');
        //$this->createGuestIfNeeded($e->getRequest());
    }

//    public function complete(FilterUserResponseEvent $e)
//    {
//        // Add ROLE_REGISTERED to the user
//        $user = $e->getUser();
//        $user->addRole(User::ROLE_REGISTERED);
//        $this->userManager->updateUser($user);
//
//        // The session has not the new ROLE_REGISTERED, this ensures it has
//        $token = $this->securityContext->getToken();
//        $token->setAuthenticated(false);
//    }


    //// UTILS /////////////////////////////////////////////////////////////////

    /**
     * Only creates a new guest User if there is none attached to this session,
     * and if there is a firewall configured for this request.
     *
     * See createGuest
     */
    public function createGuestIfNeeded(Request $request)
    {
        $token = $this->tokenStorage->getToken();

        if ($token != null && !$this->tokenStorage->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->createGuest($request);
        }
    }

    /**
     * This effectively registers a new User in the database, and attributes it
     * to the current session.
     *
     * Every guest will have its user in the database.
     * Account creation (aka: the Register flow) is merely upgrading the
     * already existing guest user, with an additional role and meaningful
     * username, email and password.
     *
     * TODO: Regularly purge database of forlorn guest accounts.
     * Guest accounts will be purged by a regularly run (CRON) routine. (yet to do)
     */
    public function createGuest(Request $request)
    {
        // 1. Create a new guest User in the database, without any special role

        /** @var User $user */
        $user = $this->userManager->createUser();

        $email = 'guest@cosmicgo.com';

        // /!\ Username as generated here may not be unique.
        $user->setUsername('Peer' . substr($user->getRestToken(), 0, 4));
        $user->setEmail($email);
        $user->setPlainPassword($this->generateRandomPassword());

//        $user->setCreatedAt(new \DateTime());
//        $user->setCreatedBy($request->getClientIp());

        // We need this or on the register page authentication will fail
        $user->setEnabled(true);

        // This will canonicalize, encode, persist and flush
        $this->userManager->updateUser($user);

        // 2. Tell our session that we're authenticated with that new User

        $providerKey = 'main';

        /** @var TokenInterface $token */
        $token = new UsernamePasswordToken(
            $user,
            $user->getPassword(),
            $providerKey,
            $user->getRoles()
        );

        $this->tokenStorage->setToken($token);

        $session = $request->getSession();
        $session->set('_security_' . $providerKey, serialize($token));

        return $user;
    }

    protected function generateRandomPassword()
    {
        // Initialize the random password
        $password = '';

        // Initialize a random desired length
        $desired_length = rand(8, 12);

        for ($length = 0; $length < $desired_length; $length++) {
            // Append a random ASCII character (including symbols)
            $password .= chr(rand(32, 126));
        }

        return $password;
    }
} 