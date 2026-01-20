<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\System;

use Ampache\Config\ConfigContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * This is a legacy implementation in order to replace the global `log_event` and `debug_event` methods
 */
final class LegacyLogger implements LoggerInterface
{
    /**
     * This emulates the Ampache log levels
     */
    public const LOG_LEVEL_CRITICAL = 1;
    public const LOG_LEVEL_ERROR    = 2;
    public const LOG_LEVEL_WARNING  = 3;
    public const LOG_LEVEL_NOTICE   = 4;
    public const LOG_LEVEL_DEBUG    = 5;

    public const CONTEXT_TYPE = 'event_type';

    private const FALLBACK_DATETIME = 'c';
    private const FALLBACK_USERNAME = 'ampache';
    private const LOG_NAME          = 'ampache';

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * @see LegacyLogger::critical (Required function to implement LoggerInterface)
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(
            self::LOG_LEVEL_CRITICAL,
            $message,
            $context
        );
    }

    /**
     * @see LegacyLogger::critical (Required function to implement LoggerInterface)
     */
    public function alert($message, array $context = []): void
    {
        $this->log(
            self::LOG_LEVEL_CRITICAL,
            $message,
            $context
        );
    }

    /**
     * debug_level = 1
     */
    public function critical($message, array $context = []): void
    {
        $this->log(
            self::LOG_LEVEL_CRITICAL,
            $message,
            $context
        );
    }

    /**
     * debug_level = 2
     */
    public function error($message, array $context = []): void
    {
        $this->log(
            self::LOG_LEVEL_ERROR,
            $message,
            $context
        );
    }

    /**
     * debug_level = 3
     */
    public function warning($message, array $context = []): void
    {
        $this->log(
            self::LOG_LEVEL_WARNING,
            $message,
            $context
        );
    }

    /**
     * debug_level = 4
     */
    public function notice($message, array $context = []): void
    {
        $this->log(
            self::LOG_LEVEL_NOTICE,
            $message,
            $context
        );
    }

    /**
     * @see LegacyLogger::notice (Required function to implement LoggerInterface)
     */
    public function info($message, array $context = []): void
    {
        $this->log(
            self::LOG_LEVEL_NOTICE,
            $message,
            $context
        );
    }

    /**
     * debug_level = 5
     */
    public function debug($message, array $context = []): void
    {
        $this->log(
            self::LOG_LEVEL_DEBUG,
            $message,
            $context
        );
    }

    /**
     * Replaces debug_event()
     */
    public function log($level, $message, array $context = []): void
    {
        if (!$this->configContainer->get('debug') || $level > $this->configContainer->get('debug_level')) {
            return;
        }

        $time_format = $this->configContainer->get('log_time_format');
        $log_time    = match ($time_format) {
            'DATE_ATOM' => date(DATE_ATOM, time()),
            'DATE_COOKIE' => date(DATE_COOKIE, time()),
            'DATE_ISO8601' => date("Y-m-d\TH:i:sO", time()),
            'DATE_ISO8601_EXPANDED' => date(DATE_ISO8601_EXPANDED, time()),
            'DATE_RFC822' => date(DATE_RFC822, time()),
            'DATE_RFC850' => date(DATE_RFC850, time()),
            'DATE_RFC1036' => date(DATE_RFC1036, time()),
            'DATE_RFC1123' => date(DATE_RFC1123, time()),
            'DATE_RFC7231' => date(DATE_RFC7231, time()),
            'DATE_RFC2822' => date(DATE_RFC2822, time()),
            'DATE_RFC3339' => date(DATE_RFC3339, time()),
            'DATE_RFC3339_EXTENDED' => date(DATE_RFC3339_EXTENDED, time()),
            'DATE_RSS' => date(DATE_RSS, time()),
            'DATE_W3C' => date(DATE_W3C, time()),
            default => (empty($time_format) || !is_string($time_format))
                ? date(self::FALLBACK_DATETIME, time())
                : date($time_format, time()),
        };

        $log_filename = $this->configContainer->get('log_filename');
        if (empty($log_filename) || !is_string($log_filename)) {
            $log_filename = "%name.%Y%m%d.log";
        }

        if (str_contains($log_filename, '%')) {
            $log_filename = str_replace("%name", self::LOG_NAME, $log_filename);
            $log_filename = str_replace("%Y", date('Y'), $log_filename);
            $log_filename = str_replace("%m", date('m'), $log_filename);
            $log_filename = str_replace("%d", date('d'), $log_filename);
        }
        $log_filename = rtrim($this->configContainer->get('log_path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $log_filename;

        $event_name = $context[self::CONTEXT_TYPE] ?? '';
        $username   = $context['username'] ?? null;
        if (empty($username)) {
            $user     = Core::get_global('user');
            $username = $user?->username ?? self::FALLBACK_USERNAME;
        }

        if (
            !error_log("$log_time [$username] ($event_name) -> $message\n", 3, $log_filename) &&
            !defined('SSE_OUTPUT') &&
            !defined('CLI') && !defined('API')
        ) {
            echo "Warning: Unable to write to log ($log_filename) Please check your log_path variable in ampache.cfg.php";
        }
    }
}
