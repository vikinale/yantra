<?php
namespace System\Utilities;

use System\Utilities\Log;

/**
 * PSR-3 compatible logger wrapper for Yantra Log facade.
 *
 * This file defines System\Utilities\PsrLogger and will implement the real
 * Psr\Log\LoggerInterface if that interface is available in the environment.
 *
 * Usage:
 *   $psr = \System\Utilities\PsrLogger::get();
 *   // pass $psr into libraries that expect Psr\Log\LoggerInterface
 *   $psr->info('User {id} created', ['id'=>123]);
 */

if (interface_exists('\Psr\Log\LoggerInterface')) {
    class_alias('\Psr\Log\LoggerInterface', '\_Psr_Log_Interface_available');
}

if (interface_exists('\_Psr_Log_Interface_available')) {
    // Define class implementing the real PSR interface
    class PsrLogger implements \Psr\Log\LoggerInterface
    {
        private static ?PsrLogger $instance = null;

        public static function get(): PsrLogger
        {
            if (self::$instance === null) {
                self::$instance = new PsrLogger();
            }
            return self::$instance;
        }

        // PSR-3 level methods
        public function emergency($message, array $context = array()): void { $this->log('emergency', $message, $context); }
        public function alert($message, array $context = array()): void     { $this->log('alert', $message, $context); }
        public function critical($message, array $context = array()): void  { $this->log('critical', $message, $context); }
        public function error($message, array $context = array()): void     { $this->log('error', $message, $context); }
        public function warning($message, array $context = array()): void   { $this->log('warning', $message, $context); }
        public function notice($message, array $context = array()): void    { $this->log('notice', $message, $context); }
        public function info($message, array $context = array()): void      { $this->log('info', $message, $context); }
        public function debug($message, array $context = array()): void     { $this->log('debug', $message, $context); }

        /**
         * Core PSR-3 method.
         *
         * @param mixed $level
         * @param string|\Stringable $message
         * @param array $context
         */
        public function log($level, $message, array $context = array()): void
        {
            // Convert Stringable to string if passed
            if (is_object($message) && method_exists($message, '__toString')) {
                $message = (string)$message;
            }

            $interpolated = $this->interpolate((string)$message, $context);
            // forward to Yantra Log facade
            Log::log($level, $interpolated, $context);
        }

        /**
         * Interpolate a message with context per PSR-3
         *
         * - Replaces {foo} with context['foo'] when present.
         * - If a context value is an exception it will be converted to string with message+trace.
         */
        protected function interpolate(string $message, array $context = []): string
        {
            if (strpos($message, '{') === false) return $message;
            $replace = [];
            foreach ($context as $k => $v) {
                if ($v instanceof \Throwable) {
                    $replace['{' . $k . '}'] = $v->getMessage() . ' in ' . $v->getFile() . ':' . $v->getLine();
                    // optionally attach trace to a context key or let the Log facade store full context
                    continue;
                }
                if (is_object($v) && method_exists($v, '__toString')) {
                    $val = (string)$v;
                } elseif (is_scalar($v) || $v === null) {
                    $val = (string)$v;
                } else {
                    // arrays/objects => JSON summary
                    $val = @json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $replace['{' . $k . '}'] = $val;
            }
            return strtr($message, $replace);
        }
    }
} else {
    // Fallback: define a PsrLogger class without implementing the PSR interface
    class PsrLogger
    {
        private static ?PsrLogger $instance = null;

        public static function get(): PsrLogger
        {
            if (self::$instance === null) {
                self::$instance = new PsrLogger();
            }
            return self::$instance;
        }

        public function emergency($message, array $context = array()): void { $this->log('emergency', $message, $context); }
        public function alert($message, array $context = array()): void     { $this->log('alert', $message, $context); }
        public function critical($message, array $context = array()): void  { $this->log('critical', $message, $context); }
        public function error($message, array $context = array()): void     { $this->log('error', $message, $context); }
        public function warning($message, array $context = array()): void   { $this->log('warning', $message, $context); }
        public function notice($message, array $context = array()): void    { $this->log('notice', $message, $context); }
        public function info($message, array $context = array()): void      { $this->log('info', $message, $context); }
        public function debug($message, array $context = array()): void     { $this->log('debug', $message, $context); }

        public function log($level, $message, array $context = array()): void
        {
            if (is_object($message) && method_exists($message, '__toString')) {
                $message = (string)$message;
            }
            $interpolated = $this->interpolate((string)$message, $context);
            Log::log($level, $interpolated, $context);
        }

        protected function interpolate(string $message, array $context = []): string
        {
            if (strpos($message, '{') === false) return $message;
            $replace = [];
            foreach ($context as $k => $v) {
                if ($v instanceof \Throwable) {
                    $replace['{' . $k . '}'] = $v->getMessage() . ' in ' . $v->getFile() . ':' . $v->getLine();
                    continue;
                }
                if (is_object($v) && method_exists($v, '__toString')) {
                    $val = (string)$v;
                } elseif (is_scalar($v) || $v === null) {
                    $val = (string)$v;
                } else {
                    $val = @json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $replace['{' . $k . '}'] = $val;
            }
            return strtr($message, $replace);
        }
    }
}
