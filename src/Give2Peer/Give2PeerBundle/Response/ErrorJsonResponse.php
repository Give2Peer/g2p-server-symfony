<?php

namespace Give2Peer\Give2PeerBundle\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class ErrorJsonResponse extends JsonResponse
{
    /**
     * @param mixed|null $message
     * @param null $code  Our internal error code
     * @param int $status The HTTP status code to send back, usually 400
     * @param array $headers
     */
    public function __construct($message, $code = null, $status = 400, $headers = array())
    {
        $data = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ];
        parent::__construct($data, 400, $headers);
    }
} 