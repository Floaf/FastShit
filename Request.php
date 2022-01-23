<?php

namespace FastShit;

class Request
{
    public $Parameters;
    public $RawData;
    public $Server;
    public $Cookie;
    public $Ip;
    public $UserAgent;
    public $ContentType;
    protected $ValidOrigin;

    public function __construct($request, $server, $cookie)
    {
        $this->Parameters   = $request;
        $this->RawData      = file_get_contents('php://input', null, null, null, 1024*1024);
        $this->Server       = $server;
        $this->Cookie       = $cookie;
        $this->Ip           = $this->Server['REMOTE_ADDR'] ?? null;
        $this->UserAgent    = $this->Server['HTTP_USER_AGENT'] ?? null;
        $this->ContentType  = $this->Server["CONTENT_TYPE"] ?? null;
        $this->ValidOrigin  = $this->IsOriginOrRefererSameAsHost();
    }

    public function GetUri() : ?string
    {
        return $this->Server['REQUEST_URI'] ?? null;
    }

    public function HasParameter($parameterName) : bool
    {
        return array_key_exists($parameterName, $this->Parameters);
    }

    public function GetParameterAsString($parameterName) : string
    {
        if (isset($this->Parameters[$parameterName])) {
            if (is_string($this->Parameters[$parameterName])) {
                if (mb_detect_encoding($this->Parameters[$parameterName], 'UTF-8', true) !== false) {
                    return $this->Parameters[$parameterName];
                }
            }
        }

        return "";
    }

    public function IsValidOrigin() : bool
    {
        return $this->ValidOrigin;
    }

    public function IsContentType(string $contentType) : bool
    {
        return ($this->ContentType === $contentType);
    }

    protected function IsOriginOrRefererSameAsHost() : bool
    {
        $origin = null;
        if (isset($this->Server['HTTP_ORIGIN'])) {
            $origin = parse_url($this->Server['HTTP_ORIGIN'], PHP_URL_HOST);
        } else if (isset($this->Server['HTTP_REFERER'])) {
            $origin = parse_url($this->Server['HTTP_REFERER'], PHP_URL_HOST);
        }


        $host = parse_url('https://' . $this->Server['HTTP_HOST'], PHP_URL_HOST);

        return $origin == $host;
    }
}
