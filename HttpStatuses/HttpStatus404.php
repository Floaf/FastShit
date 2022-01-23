<?php

namespace FastShit\HttpStatuses;

class HttpStatus404 extends HttpStatus
{
    public function OutputResponse($debugInfo)
    {
        http_response_code(404);
        print "Page not found";
    }
}
