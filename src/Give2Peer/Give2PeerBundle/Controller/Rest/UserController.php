<?php

namespace Give2Peer\Give2PeerBundle\Controller\Rest;

use Doctrine\ORM\PersistentCollection;
use Gedmo\Sluggable\Util\Urlizer;
use Give2Peer\Give2PeerBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Give2Peer\Give2PeerBundle\Controller\ErrorCode as Error;
use Give2Peer\Give2PeerBundle\Entity\Item;
use Give2Peer\Give2PeerBundle\Entity\User;
use Give2Peer\Give2PeerBundle\Response\ErrorJsonResponse;
use Give2Peer\Give2PeerBundle\Response\ExceededQuotaJsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Routes are configured in YAML, in `Resources/config/routing.yml`.
 * ApiDoc's documentation can be found at :
 * https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/index.md
 * 
 * 
 */
class UserController extends BaseController
{

    const DUMMY_EMAIL_DOMAIN = "anons.give2peer.org";

    /**
     * Generate a username from dictionaries of words.
     *
     * Refactor this into a service ?!
     * 
     * <adjective1>_<adjective2>_<being>
     *
     * @return String
     */
    public function generateUsername()
    {
        $dir = "@Give2PeerBundle/Resources/config/";
        $path = $this->get('kernel')->locateResource($dir . 'game.yml');

        $words = Yaml::parse(file_get_contents($path));

        $adjectives = array_merge(
             $words['quantity']
            ,$words['quality']
            ,$words['size']
            ,$words['age']
            ,$words['shape']
            ,$words['color']
            ,$words['origin']
            ,$words['material']
            ,$words['qualifier']
        );
        
        $beings = $words['being'];

        $a1 = $a2 = '';
        $i = 0; // BAD. pop out of $adjectives instead ?
        while ($a1 == $a2 && $i < 42) {
            $i1 = array_rand($adjectives);
            $i2 = array_rand($adjectives);
            $a1 = $adjectives[min($i1, $i2)];
            $a2 = $adjectives[max($i1, $i2)];
            $i++;
        }

        if ($a1 == $a2) { // we have a lottery winner
            $a1 = "Incredibly";
            $a2 = "Unlucky";
        }

        $b = $beings[array_rand($beings)];

        $x = random_int(0, 9);
        $y = random_int(0, 9);
        $z = random_int(0, 9);
        
        // About 406 billion right now
        // $nb = count($beings) * (count($adjectives) ** 2) * 1000;
        // print("Possibilities : $nb\n");

        return "${a1} ${a2} ${b} ${x}${y}${z}";
    }


    /**
     * Worst password generator ever.
     * @return string
     */
    public function generatePassword()
    {
        $a = $this->generateUsername();
        $b = random_int(0, 9);
        $c = random_int(0, 9);
        $d = random_int(0, 9);
        return "$a$b$c$d";
    }

    // YAML cleaner I used
//        $words = [];
//        foreach($animals as $a) {
//            $b = str_replace(['-',','], ' ', str_replace("'", '', trim($a)));
//            foreach(explode(' ', $b) as $w) {
//                if (strlen($w)) {
//                    $words[] = strtoupper($w{0}) . substr($w,1);
//                }
//            }
//        }
//
//        $words = array_unique($words);
//        sort($words);
//
//        $s = '';
//        foreach($words as $w) {
//            $s .= "- $w\n";
//        }
//        $f = fopen(__DIR__.'_colors.yml', 'w+');
//        fprintf($f, $s);
//        fclose($f);


    /**
     * Get the (private) profile information of the current user.
     * 
     * @ApiDoc()
     *
     * @param  Request $request
     * @return ErrorJsonResponse|JsonResponse
     */
    public function privateReadAction (Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (empty($user)) {  // Does this even ever happen ?
            return $this->error("user.missing");
        }

        /** @var PersistentCollection $items */
        $items = $user->getItemsAuthored(); // not sure softdeletable is applied

        return $this->respond([
            'user'  => $user,
            //'items' => $items, // /!\ PITFALL /!\ : parser thinks it's empty
            'items' => $items->getValues(), // <== do that instead
        ]);
    }
    
    /**
     * Get the (public) profile information of the user identifier by `id`.
     * 
     * @ApiDoc(
     *   requirements = {
     *     {
     *       "name"="id",
     *       "requirement"="[0-9]+", "type"="integer",
     *       "description"="The unique identifier of the user.",
     *     }
     *   }
     * )
     *
     * @param  Request $request
     * @return ErrorJsonResponse|JsonResponse
     */
    public function publicReadAction (Request $request, $id)
    {
        $user = $this->getUserById($id);

        if (empty($user)) {
            return $this->error("user.not_found.by_id", ['%id%'=>$id], 404);
        }

        return $this->respond([
            'user' => $user->publicJsonSerialize(),
        ]);
    }

    /**
     * Change the authenticated user's password to the provided `password`.
     *
     * If you need to change more than the password, use `POST user/{id}`.
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
            return $this->error("user.password.missing");
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->editAction($request, $user);
    }

    /**
     * Change the authenticated user's username to the provided `username`.
     *
     * If you need to change more than the username, use `POST user/{id}`.
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
            return $this->error("user.username.missing");
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->editAction($request, $user);
    }

    /**
     * Change the authenticated user's email to the provided `email`.
     *
     * If you need to change more than the email, use `POST user/{id}`.
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
            return $this->error("user.email.missing");
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->editAction($request, $user);
    }

    /**
     * Change the system properties of the User described by its `id`.
     *
     * #### Restrictions
     *
     * You can only change the user you're authenticated with.
     *
     * #### Thoughts
     *
     * Ideally we'd make another route for more trivial profile preferences.
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
     * The following annotation is useless. It is guessed automagically ?
     * ParamConverter("user", class="Give2PeerBundle:User")
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function editAction(Request $request, $id)
    {
        // Recover the user data
        $username = $request->get('username');
        $password = $request->get('password');
        $email    = $request->get('email');

        $clientIp = $request->getClientIp(); // use this somehow ?

        $user = $this->getUserById($id);
        if (empty($user)) {
            return $this->error("user.not_found.by_id", ['%id%'=>$id], 404);
        }

        /** @var User $user */
        $authenticatedUser = $this->getUser();
        if (null == $authenticatedUser) {  // does this ever happen?
            return $this->error("user.missing");
        }

        if ($user->getId() != $authenticatedUser->getId()) {
            return $this->error("user.edit.not_yourself");
        }

        $um = $this->getUserManager();

        // EMAIL
        if (null != $email) {
            // Rebuke if email is taken
            $existingEmailUser = $um->findUserByEmail($email);
            if (null != $existingEmailUser) {
                return $this->error("user.email.taken");
            }

            $user->setEmail($email);
        }

        // USERNAME
        if (null != $username) {
            // Rebuke if username is taken
            $existingUsernameUser = $um->findUserByUsername($username);
            if (null != $existingUsernameUser) {
                return $this->error("user.username.taken");
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
        return $this->respond(['user'=>$user]);
    }

    /**
     * Basic boring registration. (well... not really)
     * 
     * If you don't provide a `password`, we'll generate one for you and give it
     * back to you in the response
     * 
     * If you don't provide a `username`, we'll generate one for you and give it
     * back to you in the response.
     *
     * @ApiDoc(
     *   parameters = {
     *     { "name"="username", "dataType"="string", "required"=false },
     *     { "name"="password", "dataType"="string", "required"=false },
     *     { "name"="email",    "dataType"="string", "required"=false },
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
            $password_generated = $this->generatePassword();
            $password = $password_generated;
        }
        
        // If you don't provide an email, we'll generate a dummy one
        $email_generated = null;
        if (null == $email) {
            $email = Urlizer::urlize($username, '_');
            $domain = self::DUMMY_EMAIL_DOMAIN;
            $email_generated = "$email@$domain";
            $email = $email_generated;
        }

        $um = $this->getUserManager();

        // Rebuke if username is taken
        $user = $um->findUserByUsername($username);
        if (null != $user) {
            return $this->error("user.username.taken");
        }

        // Rebuke if email is taken
        $user = $um->findUserByEmail($email);
        if (null != $user) {
            return $this->error("user.email.taken");
        }

        // Rebuke if too many Users created in 3 days from this IP
        // See http://php.net/manual/fr/dateinterval.construct.php
        $allowed = 42;
        $duration = new \DateInterval("P3D");
        $since = (new \DateTime())->sub($duration);
        $count = $um->countUsersCreatedBy($clientIp, $since);
        if ($count > $allowed) {
            return $this->error("registration.quota", [], Response::HTTP_TOO_MANY_REQUESTS);
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
        return $this->respond($response);
    }


}