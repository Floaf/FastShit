<?php

declare(strict_types=1);

namespace FastShit;

class Request
{
    /** @var array<mixed> */
    public readonly array $parameters;
    public readonly ?string $rawData;

    /** @var array<mixed> */
    public readonly array $server;

    /** @var array<string, string> */
    public readonly array $cookie;
    public readonly ?string $ip;
    public readonly ?string $userAgent;
    public readonly ?string $contentType;
    protected readonly bool $validOrigin;

    /**
     * @param array<mixed> $request
     * @param array<mixed> $server
     * @param array<mixed> $cookie
     */
    public function __construct(array $request, array $server, array $cookie)
    {
        $rawData = file_get_contents('php://input', false, null, 0, 1024 * 1024);
        $ip = $server['REMOTE_ADDR'] ?? null;
        $userAgent = $server['HTTP_USER_AGENT'] ?? null;
        $contentType = $server["CONTENT_TYPE"] ?? null;

        $this->parameters = $request;
        $this->rawData = ($rawData !== false ? $rawData : null);
        $this->server = $server;
        $this->cookie = $this->FilterCookie($cookie);
        $this->ip = (is_string($ip) ? $ip : null);
        $this->userAgent = (is_string($userAgent) ? $userAgent : null);
        $this->contentType = (is_string($contentType) ? $contentType : null);
        $this->validOrigin = $this->IsOriginOrRefererSameAsHost();
    }

    public function GetUri(): ?string
    {
        $uri = $this->server['REQUEST_URI'] ?? null;
        return (is_string($uri) ? $uri : null);
    }

    public function HasParameter(string $parameterName): bool
    {
        return array_key_exists($parameterName, $this->parameters);
    }

    public function GetParameterAsString(string $parameterName): string
    {
        if (isset($this->parameters[$parameterName])) {
            $parameter = $this->parameters[$parameterName];
            if (is_string($parameter) && mb_detect_encoding($parameter, 'UTF-8', true) !== false) {
                return $parameter;
            }
        }

        return "";
    }

    public function IsValidOrigin(): bool
    {
        return $this->validOrigin;
    }

    public function IsContentType(string $contentType): bool
    {
        return ($this->contentType === $contentType);
    }

    protected function IsOriginOrRefererSameAsHost(): bool
    {
        $httpOrigin = $this->server['HTTP_ORIGIN'] ?? null;
        $httpReferer = $this->server['HTTP_REFERER'] ?? null;

        $origin = null;
        if (is_string($httpOrigin)) {
            $origin = parse_url($httpOrigin, PHP_URL_HOST);
        } elseif (is_string($httpReferer)) {
            $origin = parse_url($httpReferer, PHP_URL_HOST);
        }

        $host = null;
        $domain = $this->server['HTTP_HOST'] ?? null;
        if (is_string($domain)) {
            $host = parse_url('https://' . $domain, PHP_URL_HOST);
        }

        // Both sides must be non-empty strings; a missing origin must never match a missing host
        return is_string($origin) && is_string($host) && $origin !== '' && $host !== '' && $origin === $host;
    }

    /**
     * @param array<mixed> $cookie
     * @return array<string, string>
     */
    private function FilterCookie(array $cookie): array
    {
        $filteredArray = [];
        foreach ($cookie as $key => $value) {
            if (is_string($value)) {
                $filteredArray[(string)$key] = $value;
            }
        }

        return $filteredArray;
    }
}
