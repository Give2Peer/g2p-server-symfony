<?php

namespace Give2Peer\Give2PeerBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ErrorJsonResponse
 * @package Give2Peer\Give2PeerBundle\Response
 *
 * We explicitly send back this response whenever there's an error.
 * We don't use implicit type casting in controller actions because we could not
 * figure out how to send back errors such as this.
 */
class ErrorJsonResponse extends JsonResponse
{
    /**
     * @param string $message A localized message to display to the user.
     * @param string $code    Our error code, eg: `api.error.user.email.taken`.
     * @param int    $status  The HTTP status code to send back, usually 400.
     * @param array  $headers Extra HTTP headers.
     */
    public function __construct($message, $code = null, $status = Response::HTTP_BAD_REQUEST, $headers = array())
    {
        $data = [
            'error' => [
                'code'    => $code,
                'message' => $message,
            ]
        ];
        parent::__construct($data, $status, $headers);
    }
} 