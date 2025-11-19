<?php

// helpers/log_helper.php
use System\Utilities\Log;

/**
 * log() helper
 * - log('info', 'message', ['x'=>1])
 * - log('error', 'message')
 * - log()->info('...') returns adapter? We keep it simple.
 */
if (!function_exists('log')) {
    function log($level = null, $message = null, array $context = [])
    {
        // ensure Log is initialised
        \System\Utilities\Log::init();

        if ($level === null) {
            // return facade (class) for fluent style: log()->info(...) isn't supported; return class name
            return \System\Utilities\Log::class;
        }

        if (is_string($level) && $message === null) {
            // allow: log('info: message {id}', ['id'=>1]) or log('info','message',[])
            // If message omitted but level contains colon, split
            if (strpos($level, ':') !== false) {
                [$lvl, $msg] = explode(':', $level, 2);
                \System\Utilities\Log::log(trim($lvl), trim($msg), $context);
                return;
            }
        }

        if (is_string($level) && is_string($message)) {
            \System\Utilities\Log::log($level, $message, $context);
            return;
        }

        // fallback - ignore
    }
}
