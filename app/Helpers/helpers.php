<?php

if (!function_exists('log_debug')) {
    /**
     * @param $message
     * @param array $context
     * @param string|null $chanel
     * @return void
     */
    function log_debug($message, array $context = [], ?string $chanel = 'debug'): void
    {
        logger()->channel($chanel)->debug(
            is_string($message) ? $message : json_pretty($message),
            $context
        );
    }
}

if (!function_exists('json_pretty')) {
    /**
     * @param $value
     * @param int $options
     * @param int $depth
     * @return false|string
     */
    function json_pretty($value, int $options = 0, int $depth = 512): bool|string
    {
        return json_encode($value, $options + JSON_PRETTY_PRINT, $depth);
    }
}

if (!function_exists('get_objects_in_directory')) {
    /**
     * @param $path
     * @param $namespace
     * @return array
     */
    function get_objects_in_directory($path, $namespace): array
    {
        $files = scandir($path);
        $objects = [];
        foreach($files as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $classname = pathinfo(basename($file), PATHINFO_FILENAME);

            if (class_exists($namespace . $classname)) {
                $object = resolve($namespace . $classname);

                $objects[] = $object;
            }
        }

        return $objects;
    }
}
