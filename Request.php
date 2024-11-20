<?php

namespace FastShit;

class Request
{
    /** @var array<string,string>|array<string,string[]> */
    public array $Parameters;
    public ?string $RawData;

    /** @var string[] */
    public array $Server;

    /** @var string[] */
    public array $Cookie;
    public ?string $Ip;
    public ?string $UserAgent;
    public ?string $ContentType;
    protected bool $ValidOrigin;

    /**
     * @param array<string,string>|array<string,string[]> $request
     * @param string[] $server
     * @param array<string,string>|array<string,string[]> $cookie
     */
    public function __construct(array $request, array $server, array $cookie)
    {
        $rawData = file_get_contents('php://input', false, null, 0, 1024 * 1024);

        $this->Parameters = $request;
        $this->RawData = ($rawData !== false ? $rawData : null);
        $this->Server = $server;
        $this->Cookie = $this->FilterCookie($cookie);
        $this->Ip = $this->Server['REMOTE_ADDR'] ?? null;
        $this->UserAgent = $this->Server['HTTP_USER_AGENT'] ?? null;
        $this->ContentType = $this->Server["CONTENT_TYPE"] ?? null;
        $this->ValidOrigin = $this->IsOriginOrRefererSameAsHost();
    }

    public function GetUri(): ?string
    {
        return $this->Server['REQUEST_URI'] ?? null;
    }

    public function HasParameter(string $parameterName): bool
    {
        return array_key_exists($parameterName, $this->Parameters);
    }

    public function GetParameterAsString(string $parameterName): string
    {
        if (isset($this->Parameters[$parameterName])) {
            $parameter = $this->Parameters[$parameterName];
            if (is_string($parameter) && mb_detect_encoding($parameter, 'UTF-8', true) !== false) {
                return $parameter;
            }
        }

        return "";
    }

    public function IsValidOrigin(): bool
    {
        return $this->ValidOrigin;
    }

    public function IsContentType(string $contentType): bool
    {
        return ($this->ContentType === $contentType);
    }

    protected function IsOriginOrRefererSameAsHost(): bool
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

    /**
     * @param array<string,string>|array<string,string[]> $cookie
     * @return string[]
     */
    private function FilterCookie(array $cookie): array
    {
        $filteredArray = [];
        foreach ($cookie as $key => $value) {
            if (is_string($value)) {
                $filteredArray[$key] = $value;
            }
        }

        return $filteredArray;
    }
}
