<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Gedmo\Sluggable\Util\Urlizer;
use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Give2Peer\Give2PeerBundle\Entity\Thank;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Give2Peer\Give2PeerBundle\Controller\ErrorCode as Error;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Give2Peer\Give2PeerBundle\Response\ExceededQuotaJsonResponse;
use Symfony\Component\Yaml\Yaml;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Routes are configured in YAML, in `Resources/config/routing.yml`.
 * ApiDoc's documentation can be found at :
 * https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.md
 * 
 * 
 */
class SocialController extends BaseController
{

    /**
     * Thank the author of the Item `id`.
     * You can only thank for a specific item once.
     * 
     * /!\
     *   This will cost karma points to use.
     *   (it does not, right now)
     * 
     * @ApiDoc()
     *
     * @param  Request $request
     * @return ErrorJsonResponse|JsonResponse
     */
    public function thankForItemAction (Request $request, Item $item)
    {
        /** @var User $thanker */
        $thanker = $this->getUser();

        if (empty($thanker)) {
            return new ErrorJsonResponse("Nope.", Error::NOT_AUTHORIZED);
        }

        $thankee = $item->getAuthor();

        // Disallow thanking more than once for the same item
        $doneAlready = $this->getThankRepository()->findOneBy([
            'thanker' => $thanker,
            'item' => $item,
        ]);
        if ($doneAlready) {
            return new ErrorJsonResponse(
                "One thanks per item only.",
                Error::EXCEEDED_QUOTA
            );
        }

        // GAME DESIGN
        // The idea is to give karma to make karma.
        // Thanker should choose how much karma he gives,
        // and it should be multiplied by a coefficient
        // that depends on its level, and thankee should receive it all.
        // But if you just levelled up you won't lose any karma so you can't
        // lose your level.
        // Let's say for now the coefficient is the level, and users don't lose
        // karma. They will. Maybe even before release.
        $karma_given = 0;
        $karma_received = $thanker->getLevel() + 1;
        ///

        $thanker->addKarma(-1 * $karma_given);
        $thankee->addKarma($karma_received);

        $thank = new Thank();
        $thank->setItem($item);
        $thank->setThanker($thanker);
        $thank->setThankee($item->getAuthor());
        $thank->setKarmaReceived($karma_received);
        $thank->setKarmaGiven($karma_given);
        
        $em = $this->getEntityManager();
        $em->persist($thank);
        $em->flush();
        
        return new JsonResponse([
            'thank'  => $thank,
        ]);
    }
    
    /**
     * Get the (public) profile information of the given user `id`.
     * 
     * @ApiDoc()
     *
     * @param  Request $request
     * @return ErrorJsonResponse|JsonResponse
     *
     * Milo : 0563764240
     *
     */
    public function publicReadAction (Request $request, User $user)
    {
        if (empty($user)) {
            return new ErrorJsonResponse("Bad username.", Error::BAD_USERNAME);
        }

        return new JsonResponse([
            'user' => $user->publicJsonSerialize(),
        ]);
    }

    /**
     * Change the authenticated user's password to the provided `password`.
     *
     * If you need to change more than the password, POST `users/{id}`.
     *
     * @ApiDoc(
     *   parameters = {
     *     { "name"="password", "dataType"="string", "required"=true },
     *   }
     * )
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function changePasswordAction(Request $request)
    {
        $password = $request->get('password');
        if (null == $password) {
            return new JsonResponse(["error"=>"No password provided."], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->editAction($request, $user);
    }

    /**
     * Change the authenticated user's username to the provided `username`.
     *
     * If you need to change more than the username, POST `users/{id}`.
     *
     * @ApiDoc(
     *   parameters = {
     *     { "name"="username", "dataType"="string", "required"=true },
     *   }
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changeUsernameAction(Request $request)
    {
        $username = $request->get('username');
        if (null == $username) {
            return new JsonResponse(["error"=>"No username provided."], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->editAction($request, $user);
    }

    /**
     * Change the authenticated user's email to the provided `email`.
     *
     * If you need to change more than the email, POST `users/{id}`.
     *
     * @ApiDoc(
     *   parameters = {
     *     { "name"="email", "dataType"="string", "required"=true },
     *   }
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changeEmailAction(Request $request)
    {
        $email = $request->get('email');
        if (null == $email) {
            return new JsonResponse(["error"=>"No email provided."], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->editAction($request, $user);
    }

    /**
     * Change the system properties of a User.
     * You can only change the user you're authenticated with.
     *
     * Ideally we'll make another route for more trivial profile preferences.
     * Maybe ?
     *
     * The flow to change such important properties may be different. But how ?
     *
     * @ApiDoc(
     *   parameters = {
     *     { "name"="username", "dataType"="string", "required"=false },
     *     { "name"="password", "dataType"="string", "required"=false },
     *     { "name"="email",    "dataType"="string", "required"=false },
     *   }
     * )
     *
     *
     * ParamConverter("user", class="Give2PeerBundle:User")
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function editAction(Request $request, User $user)
    {
        // Recover the user data
        $username = $request->get('username');
        $password = $request->get('password');
        $email    = $request->get('email');

        $clientIp = $request->getClientIp(); // use this somehow ?

        /** @var User $user */
        $authenticatedUser = $this->getUser();

        if (null == $user || null == $authenticatedUser) {
            return new JsonResponse(["error"=>"No user."], 400);
        }

        if ($user->getId() != $authenticatedUser->getId()) {
            return new ErrorJsonResponse(
                "You can only edit yourself.", Error::NOT_AUTHORIZED
            );
        }

        $um = $this->getUserManager();

        // EMAIL
        if (null != $email) {
            // Rebuke if email is taken
            $existingEmailUser = $um->findUserByEmail($email);
            if (null != $existingEmailUser) {
                return new ErrorJsonResponse(
                    "Email already taken.", Error::UNAVAILABLE_EMAIL
                );
            }

            $user->setEmail($email);
        }

        // USERNAME
        if (null != $username) {
            // Rebuke if username is taken
            $existingUsernameUser = $um->findUserByUsername($username);
            if (null != $existingUsernameUser) {
                return new ErrorJsonResponse(
                    "Username already taken.", Error::UNAVAILABLE_USERNAME
                );
            }

            $user->setUsername($username);
        }

        // PASSWORD
        if (null != $password) {
            $user->setPlainPassword($password);
        }

        // If we changed nothing, we're OK.
        // Maybe send back an appropriate NOTHING CHANGED HTTP code ?

        // This canonicalizes, encodes, persists and flushes
        $um->updateUser($user);

        // Send the user as response
        return new JsonResponse(['user'=>$user]);
    }

    /**
     * Basic boring registration. (well... not really)
     * 
     * If you don't provide a password, we'll generate one for you and give it
     * back to you in the response
     * 
     * If you don't provide a username, we'll generate one for you and give it
     * back to you in the response.
     *
     * @ApiDoc(
     *   parameters = {
     *     { "name"="username", "dataType"="string", "required"=false },
     *     { "name"="password", "dataType"="string", "required"=false },
     *     { "name"="email",    "dataType"="string", "required"=true  },
     *   }
     * )
     * @param Request $request
     * @return JsonResponse
     */
    public function registerAction(Request $request)
    {
        // Recover the user data
        $username = $request->get('username');
        $password = $request->get('password');
        $email    = $request->get('email');
        $clientIp = $request->getClientIp();

        // If you don't provide a username, we'll generate one for you
        // and give it back to you in the response.
        $username_generated = null;
        if (null == $username) {
            $username_generated = $this->generateUsername();
            $username = $username_generated;
        }
        
        // If you don't provide a password, we'll generate one for you
        // and give it back to you in the response.
        $password_generated = null;
        if (null == $password) {
            $password_generated = "I swear I'm here to help."; // fixme, you lazy fsck
            $password = $password_generated;
        }
        
        // If you don't provide an email, we'll generate a dummy one
        $email_generated = null;
        if (null == $email) {
            $email = Urlizer::urlize($username, '_');
            $email_generated = "$email@dummies.give2peer.org";
            $email = $email_generated;
        }

        $um = $this->getUserManager();

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

        // Rebuke if too many Users created in 3 days from this IP
        // See http://php.net/manual/fr/dateinterval.construct.php
        $allowed = 42;
        $duration = new \DateInterval("P3D");
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

        // This canonicalizes, encodes, persists and flushes
        $um->updateUser($user);

        // Send the user in the response
        $response = ['user' => $user];
        // Along with the username if one was generated
        if (null != $username_generated) {
            $response['username'] = $username_generated;
        }
        // Along with the password if one was generated
        if (null != $password_generated) {
            $response['password'] = $password_generated;
        }
        return new JsonResponse($response);
    }


}