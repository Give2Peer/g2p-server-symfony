<?php

namespace Give2Peer\Give2PeerBundle\Response;

use Give2Peer\Give2PeerBundle\Controller\ErrorCode;


class ExceededQuotaJsonResponse extends ErrorJsonResponse
{
    /**
     * A special case of error JSON responses sent back when there were too many
     * requests, usually because a user has exceeded his daily quota.
     *
     * @param mixed|null $message
     * @param array      $headers
     */
    public function __construct($message, $headers = array())
    {
        // 429 is HTTP code for Too Many Requests
        parent::__construct($message, ErrorCode::EXCEEDED_QUOTA, 429, $headers);
    }
} 