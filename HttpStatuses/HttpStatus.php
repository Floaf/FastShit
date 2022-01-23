<?php

namespace FastShit\HttpStatuses;

use FastShit\Request;
use Exception;

abstract class HttpStatus extends Exception
{
    protected $Request = null;

    public function __construct(Request $request)
    {
        $this->Request = $request;
    }

    public abstract function OutputResponse($debugInfo);
}
