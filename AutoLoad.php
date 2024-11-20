<?php

class AutoLoad
{
    public static function RegisterNamespace(string $namespace, string $dirName): void
    {
        spl_autoload_register(function (string $className) use ($namespace, $dirName): void {
            $classNameStart = $namespace . "\\";
            if (mb_strpos($className, $classNameStart) === 0) {
                $className = mb_substr($className, mb_strlen($classNameStart));

                if ($dirName == null) {
                    $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
                } else {
                    $fileName = $dirName . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
                }

                if (file_exists($fileName)) {
                    require_once $fileName;
                }
            }
        });
    }
}
