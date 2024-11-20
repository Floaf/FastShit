<?php

namespace FastShit\HttpStatuses;

use FastShit\Request;
use Exception;

abstract class HttpStatus extends Exception
{
    protected Request $Request;

    public function __construct(Request $request)
    {
        $this->Request = $request;
    }

    public abstract function OutputResponse(?string $debugInfo): void;
}
