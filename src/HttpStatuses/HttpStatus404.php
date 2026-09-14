<?php

declare(strict_types=1);

namespace FastShit\HttpStatuses;

class HttpStatus404 extends HttpStatus
{
    #[\Override]
    public function OutputResponse(?string $debugInfo): void
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        print "Page not found";
        if ($debugInfo !== null) {
            print $debugInfo;
        }
    }
}
