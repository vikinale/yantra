<?php
namespace Core\Controllers;

use Core\LowCode\LowCode;
use Core\LowCode\ModuleManager;
use Core\LowCode\ModuleNotFoundException;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use System\Config;

class BaseController
{
    private string $blockDIR ="App/UIModules";
    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->init();
        LowCode::init();
    }

    private function init(): void
    {
        global $env;
        $env->theme = apply_filter('get_theme', $env->theme, Config::get('theme'));
    }

    /**
     * @throws ModuleNotFoundException
     */
    protected function registerUIModule(string $name, string $dir = null): void
    {
        ModuleManager::register($name, $dir??$this->blockDIR);
    }

    /**
     * Convert any date or datetime string to MySQL date format (YYYY-MM-DD).
     *
     * @param string $dateString The date or datetime string to convert.
     * @return string|null The MySQL date format or null if the conversion fails.
     */
    /**
     * Convert various date formats to MySQL date format (YYYY-MM-DD).
     *
     * @param string $dateString The date string to convert.
     * @return string The MySQL date format.
     * @throws InvalidArgumentException If the input string cannot be converted.
     */
    protected function toMysqlDate(string $dateString): string
    {
        $formats = [
            'd M, Y',        // Example: 10 Sep, 2024
            'd/m/Y',         // Example: 10/09/2024
            'Y-m-d',         // Example: 2024-09-10
            'm/d/Y',         // Example: 09/10/2024
            'd M Y',         // Example: 10 Sep 2024
            'd-m-Y',         // Example: 10-09-2024
        ];

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date && $date->format($format) === $dateString) {
                return $date->format('Y-m-d');
            }
        }

        throw new InvalidArgumentException("Invalid date format: $dateString");
    }

    /**
     * Convert various datetime formats to MySQL datetime format (YYYY-MM-DD HH:MM:SS).
     *
     * @param string $datetimeString The datetime string to convert.
     * @return string The MySQL datetime format.
     * @throws InvalidArgumentException If the input string cannot be converted.
     */
    protected function toMysqlDateTime(string $datetimeString): string
    {
        $formats = [
            'd M, Y H:i:s',   // Example: 10 Sep, 2024 14:30:00
            'd/m/Y H:i:s',    // Example: 10/09/2024 14:30:00
            'Y-m-d H:i:s',    // Example: 2024-09-10 14:30:00
            'Y-m-d H:i',      // Example: 2024-09-10 14:30
            'd M Y H:i',      // Example: 10 Sep 2024 14:30
            'd/m/Y H:i',      // Example: 10/09/2024 14:30
        ];

        foreach ($formats as $format) {
            $datetime = DateTime::createFromFormat($format, $datetimeString);
            if ($datetime && $datetime->format($format) === $datetimeString) {
                return $datetime->format('Y-m-d H:i:s');
            }
        }

        throw new InvalidArgumentException("Invalid datetime format: $datetimeString");
    }

    /**
     * Convert various time formats to MySQL time format (HH:MM:SS).
     *
     * @param string $timeString The time string to convert.
     * @return string The MySQL time format.
     * @throws InvalidArgumentException If the input string cannot be converted.
     */
    protected function toMysqlTime(string $timeString): string
    {
        $formats = [
            'H:i:s',         // Example: 14:30:00
            'H:i',           // Example: 14:30
        ];

        foreach ($formats as $format) {
            $time = DateTime::createFromFormat($format, $timeString);
            if ($time && $time->format($format) === $timeString) {
                return $time->format('H:i:s');
            }
        }

        throw new InvalidArgumentException("Invalid time format: $timeString");
    }

    protected function fromMysqlDate(string $mysqlDate, string $outputFormat = 'd M Y'): ?string
    {
        $date = null;

        // Define formats to try
        $formats = [
            'Y-m-d H:i:s',  // Datetime
            'Y-m-d',        // Date
            'H:i:s',        // Time
            DateTimeInterface::ATOM  // ISO 8601 (including timezone)
        ];

        // Try parsing with each format
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $mysqlDate);

            // Check if the date object is created successfully
            if ($date && $date->format($format) === $mysqlDate) {
                // Return formatted date, datetime, or time
                return $date->format($outputFormat);
            }
        }

        // Throw an exception if none of the formats matched
        throw new InvalidArgumentException("Invalid date format: $mysqlDate");
    }
}
