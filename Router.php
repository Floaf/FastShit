<?php

namespace FastShit;

use FastShit\HttpStatuses\HttpStatus;
use FastShit\HttpStatuses\HttpStatus404;
use FastShit\HttpStatuses\HttpStatus500;
use Exception;

class Router
{
    protected string $ControllerNamespace;
    protected string $ControllerName;
    protected Request $Request;
    protected bool $DevEnvironment = false;

    /** @var array<int, class-string<HttpStatus>> */
    protected array $HttpStatusClasses = [
        404 => HttpStatus404::class,
        500 => HttpStatus500::class
    ];

    public function __construct(string $controllerNamespace, string $controllerName, Request $request, bool $devEnvironment = false)
    {
        $this->ControllerNamespace = $controllerNamespace;
        $this->ControllerName = $controllerName;
        $this->Request = $request;

        $this->DevEnvironment = $devEnvironment;
    }

    /** @param class-string<HttpStatus> $class */
    public function SetHttpStatusClass(int $httpStatus, string $class): void
    {
        $this->HttpStatusClasses[$httpStatus] = $class;
    }

    public function HandleRequest(string $uri): void
    {
        $testedClasses = [];

        try {
            $uriParts = self::SplitOnQueryString($uri);

            // Does the request end with a slash?
            $endsWithSlash = (mb_substr($uriParts->path, -1) === '/');

            // Split the request into segments
            $requestSegments = explode('/', trim($uriParts->path, '/'));

            // Path for controller
            $controllerPathArray = [
                ''
            ];
            $folderSegment = '';

            // Path for url
            $urlPathArray = [
                '/'
            ];
            $pathSegment = '/';
            foreach ($requestSegments as $requestSegment) {
                if ($requestSegment != null) {
                    $folderSegment .= self::ConvertToStudlyCaps($requestSegment) . '/';
                    $controllerPathArray[] = $folderSegment;

                    $pathSegment .= $requestSegment . '/';
                    $urlPathArray[] = $pathSegment;
                }
            }
            $controllerPathArray = array_reverse($controllerPathArray);
            $urlPathArray = array_reverse($urlPathArray);

            // When the path do not end with a slash, check if the last path segment corresponds with a method name in the controller class
            if (!$endsWithSlash && isset($controllerPathArray[1])) {
                $controller = str_replace('/', '\\', $controllerPathArray[1]);
                $controllerClass = $this->ControllerNamespace . $controller . $this->ControllerName;

                $methodName = self::ConvertToStudlyCaps(end($requestSegments));
                $methodAction = $methodName . 'CustomAction';

                $baseUrl = ($urlPathArray[1] ?? '') . end($requestSegments);

                if (class_exists($controllerClass)) {
                    if (method_exists($controllerClass, $methodAction)) {
                        $class = new $controllerClass($this->Request, $baseUrl);
                        $class->$methodAction(); // @phpstan-ignore method.dynamicName
                        return;
                    }
                }

                $testedClasses[] = $controllerClass . '->' . $methodAction;
            }

            // Check all allowed combinations of the path, beginning with the most accurate
            foreach ($controllerPathArray as $level => $currentPath) {
                $controller = str_replace('/', '\\', $currentPath);
                $controllerClass = $this->ControllerNamespace . $controller . $this->ControllerName;

                // Level 0 means that the path points to a specific method
                $methodAction = ($level == 0 ? 'IndexAction' : 'RelativeAction');

                if (class_exists($controllerClass)) {
                    if (method_exists($controllerClass, $methodAction)) {
                        if (!$endsWithSlash) {
                            $redirect = $uriParts->path . '/' . ($uriParts->queryString !== null ? '?' . $uriParts->queryString : '');
                            header('Location: ' . $redirect, true, ($this->DevEnvironment ? 302 : 301));
                            return;
                        } else if ($uriParts->path !== (rtrim($uriParts->path, '/') . '/')) {
                            $redirect = rtrim($uriParts->path, '/') . '/' . ($uriParts->queryString !== null ? '?' . $uriParts->queryString : '');
                            header('Location: ' . $redirect, true, ($this->DevEnvironment ? 302 : 301));
                            return;
                        }

                        $path = $urlPathArray[$level] ?? throw new Exception('Path not found');
                        $class = new $controllerClass($this->Request, $urlPathArray[$level]);
                        $relativePath = mb_substr($uriParts->path, mb_strlen($urlPathArray[$level]));
                        $class->$methodAction($relativePath); // @phpstan-ignore method.dynamicName
                        return;
                    }
                }

                $testedClasses[] = $controllerClass . '->' . $methodAction;
            }

            throw new ($this->GetStatusClassName(404))($this->Request);
        } catch (HttpStatus $ex) {
            $debugInfo = null;

            if ($this->DevEnvironment) {
                $debugInfo = "\n\nTested classes:\n";
                $debugInfo .= implode("\n", $testedClasses);
            }

            $ex->OutputResponse($debugInfo);
        } catch (Exception $ex) {
            $debugInfo = null;

            if ($this->DevEnvironment) {
                $debugInfo = "\n\n" . $ex->getMessage();
            }

            $status = new ($this->GetStatusClassName(500))($this->Request);
            $status->OutputResponse($debugInfo);
        }
    }

    /** @return class-string<HttpStatus> */
    private function GetStatusClassName(int $statusCode): string
    {
        return $this->HttpStatusClasses[$statusCode] ?? throw new Exception('No status class defined for status code ' . $statusCode);
    }

    private static function SplitOnQueryString(string $url): SplitOnQueryStringResult
    {
        $queryString = null;

        $segments = explode('?', $url);
        $path = $segments[0];
        if (count($segments) == 2) {
            $queryString = $segments[1];
        }

        return new SplitOnQueryStringResult(
            $path,
            $queryString
        );
    }

    private static function ConvertToStudlyCaps(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace([
            '-',
            '_'
        ], ' ', $string)));
    }
}

readonly class SplitOnQueryStringResult
{
    public string $path;
    public ?string $queryString;

    public function __construct(string $path, ?string $queryString)
    {
        $this->path = $path;
        $this->queryString = $queryString;
    }
}
