<?php

if (!function_exists('log_debug')) {
    /**
     * @param $message
     * @param array $context
     */
    function log_debug($message, array $context = []): void
    {
        logger()->debug(
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
