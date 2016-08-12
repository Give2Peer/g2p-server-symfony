<?php

namespace Give2Peer\Give2PeerBundle\Controller;

/**
 * Error codes returned by the REST API when something fishy happens.
 * 
 * The response will usually have an HTTP error code (4XX), the following error
 * codes will be in the content, under the `code` key, such as :
 * 
 * { "code": 8, ... }
 */
abstract class ErrorCode
{
    const UNAVAILABLE_USERNAME =  1; // For registration only
    const BANNED_FOR_ABUSE     =  2; // Ooooops !
    const UNSUPPORTED_FILE     =  3; // For item picture uploads
    const NOT_AUTHORIZED       =  4; // Miscellaneous fails
    const SYSTEM_ERROR         =  5; // Should NEVER happen in production
    const BAD_LOCATION         =  6; // Provided location cannot be resolved
    const UNAVAILABLE_EMAIL    =  7; // For registration only
    const EXCEEDED_QUOTA       =  8; // Users have daily quotas for some actions
    const BAD_USERNAME         =  9; // Provided username cannot be resolved
    const BAD_USER_ID          = 10; // Provided user id cannot be resolved
    const BAD_ITEM_TYPE        = 11; // Provided item type is unhandled
    const LEVEL_TOO_LOW        = 12; // User level is too low 
    const ALREADY_DONE         = 13; // Action could only be done once
}