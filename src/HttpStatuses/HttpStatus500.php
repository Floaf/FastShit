<?php

declare(strict_types=1);

namespace FastShit\HttpStatuses;

class HttpStatus500 extends HttpStatus
{
    #[\Override]
    public function OutputResponse(?string $debugInfo): void
    {
        http_response_code(500);
        print "Something went wrong";
    }
}
