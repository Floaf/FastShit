<?php

namespace FastShit;

use FastShit\HttpStatuses\HttpStatus;
use FastShit\HttpStatuses\HttpStatus404;
use FastShit\HttpStatuses\HttpStatus500;
use Exception;
use stdClass;

class Router
{
    protected $ControllerNamespace;
    protected $ControllerName;
    protected $Request;
    protected $DevEnvironment = false;
    protected $HttpStatusClasses = [
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

    public function SetHttpStatusClass(int $httpStatus, string $class)
    {
        $this->HttpStatusClasses[$httpStatus] = $class;
    }

    public function HandleRequest($uri)
    {
        $testedClasses = [];

        try {
            $uriParts = static::SplitOnQueryString($uri);

            // Does the request end with a slash?
            $endsWithSlash = (mb_substr($uriParts->Path, -1) === '/');

            // Split the request into segments
            $requestSegments = explode('/', trim($uriParts->Path, '/'));

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
                    $folderSegment .= $this->ConvertToStudlyCaps($requestSegment) . '/';
                    $controllerPathArray[] = $folderSegment;

                    $pathSegment .= $requestSegment . '/';
                    $urlPathArray[] = $pathSegment;
                }
            }
            $controllerPathArray = array_reverse($controllerPathArray);
            $urlPathArray = array_reverse($urlPathArray);

            // When the path do not end with a slash, check if the last path segment corresponds with a method name in the controller class
            if (!$endsWithSlash && count($controllerPathArray) >= 2) {
                $controller = str_replace('/', '\\', $controllerPathArray[1]);
                $controllerClass = $this->ControllerNamespace . $controller . $this->ControllerName;

                $methodName = $this->ConvertToStudlyCaps(end($requestSegments));
                $methodAction = $methodName . 'CustomAction';

                $baseUrl = $urlPathArray[1] . end($requestSegments);

                if (class_exists($controllerClass)) {
                    if (method_exists($controllerClass, $methodAction)) {
                        $class = new $controllerClass($this->Request, $baseUrl);
                        $class->$methodAction();
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
                            $redirect = $uriParts->Path . '/' . ($uriParts->QueryString !== null ? '?' . $uriParts->QueryString : '');
                            header('Location: ' . $redirect, true, ($this->DevEnvironment ? 302 : 301));
                            return;
                        } else if ($uriParts->Path !== (rtrim($uriParts->Path, '/') . '/')) {
                            $redirect = rtrim($uriParts->Path, '/') . '/' . ($uriParts->QueryString !== null ? '?' . $uriParts->QueryString : '');
                            header('Location: ' . $redirect, true, ($this->DevEnvironment ? 302 : 301));
                            return;
                        }

                        $class = new $controllerClass($this->Request, $urlPathArray[$level]);
                        $relativePath = mb_substr($uriParts->Path, mb_strlen($urlPathArray[$level]));
                        $class->$methodAction($relativePath);
                        return;
                    }
                }

                $testedClasses[] = $controllerClass . '->' . $methodAction;
            }

            throw new $this->HttpStatusClasses[404]($this->Request);
        } catch (HttpStatus $ex) {
            $debugInfo = null;

            if ($this->DevEnvironment === true) {
                $debugInfo = "\n\nTested classes:\n";
                $debugInfo .= implode("\n", $testedClasses);
            }

            $ex->OutputResponse($debugInfo);
        } catch (Exception $ex) {
            $debugInfo = null;

            if ($this->DevEnvironment === true) {
                $debugInfo = "\n\n" . $ex->getMessage();
            }

            $status = new $this->HttpStatusClasses[500]($this->Request);
            $status->OutputResponse($debugInfo);
        }
    }

    protected static function SplitOnQueryString($url)
    {
        $result = new stdClass();
        $result->Path = null;
        $result->QueryString = null;

        $segments = explode('?', $url);
        $result->Path = $segments[0];
        if (count($segments) == 2) {
            $result->QueryString = $segments[1];
        }

        return $result;
    }

    protected function ConvertToStudlyCaps($string)
    {
        return str_replace(' ', '', ucwords(str_replace([
            '-',
            '_'
        ], ' ', $string)));
    }
}
