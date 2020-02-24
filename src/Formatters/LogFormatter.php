<?php

namespace Ultraleet\WcErply\Formatters;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class LogFormatter extends LineFormatter
{
    /**
     * Translates Monolog log levels to html color priorities.
     */
    protected $logLevels = [
        Logger::DEBUG     => ['bg' => '#cccccc', 'fg' => '#000000'],
        Logger::INFO      => ['bg' => '#468847', 'fg' => '#ffffff'],
        Logger::NOTICE    => ['bg' => '#3a87ad', 'fg' => '#ffffff'],
        Logger::WARNING   => ['bg' => '#c09853', 'fg' => '#ffffff'],
        Logger::ERROR     => ['bg' => '#f0ad4e', 'fg' => '#ffffff'],
        Logger::CRITICAL  => ['bg' => '#FF7708', 'fg' => '#ffffff'],
        Logger::ALERT     => ['bg' => '#C12A19', 'fg' => '#ffffff'],
        Logger::EMERGENCY => ['bg' => '#000000', 'fg' => '#ffffff'],
    ];


    public function format(array $record)
    {
        $line = parent::format($record);
        $bgColor = $this->logLevels[$record['level']]['bg'];
        $fgColor = $this->logLevels[$record['level']]['fg'];

        $line = "<div style='color: $fgColor; background-color: $bgColor'>$line</div>\n";

        return $line;
    }
}
