<?php

namespace FastShit\HttpStatuses;

class HttpStatus500 extends HttpStatus
{
    public function OutputResponse($debugInfo)
    {
        http_response_code(500);
        print "Something went wrong";
    }
}
