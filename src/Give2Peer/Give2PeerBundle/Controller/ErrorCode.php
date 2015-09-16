<?php

namespace Give2Peer\Give2PeerBundle\Controller;

/**
 * Error codes returned by the REST API when something fishy happens.
 *
 * Class ErrorCode
 * @package Give2Peer\Give2PeerBundle\Controller
 */
abstract class ErrorCode
{
    const UNAVAILABLE_USERNAME = 1; // For registration only
    const BANNED_FOR_ABUSE     = 2; // Ooooops !
    const UNSUPPORTED_FILE     = 3; // For item picture uploads
    const NOT_AUTHORIZED       = 4;
    const SYSTEM_ERROR         = 5; // Should NEVER happen in production
    const BAD_LOCATION         = 6; // Provided location cannot be resolved
    const UNAVAILABLE_EMAIL    = 7; // For registration only
    const EXCEEDED_QUOTA       = 8; // Users have daily quotas for some actions
}