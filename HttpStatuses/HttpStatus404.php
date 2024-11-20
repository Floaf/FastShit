<?php

namespace FastShit\HttpStatuses;

class HttpStatus404 extends HttpStatus
{
    #[\Override]
    public function OutputResponse(?string $debugInfo): void
    {
        http_response_code(404);
        print "Page not found";
    }
}
