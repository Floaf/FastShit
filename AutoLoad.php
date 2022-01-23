<?php

class AutoLoad
{
    public static function RegisterNamespace($namespace, $dirName)
    {
        spl_autoload_register(function ($className) use ($namespace, $dirName) {
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
