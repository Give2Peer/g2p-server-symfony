<?php

namespace Give2Peer\Give2PeerBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Routes that are only useful for internal testing.
 */
class TestController extends BaseController
{

    /**
     * This is the action that's used in the features to test localization.
     */
    public function greetAction()
    {
        return $this->respond($this->getTranslator()->trans('test.greet'));
    }

}
