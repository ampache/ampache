<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=1);

namespace Ampache\Module\System;

use Ampache\Config\ConfigContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * This is a legacy implementation in order to replace the global `log_event` and `debug_event` methods
 */
final class LegacyLogger implements LoggerInterface
{
    /**
     * This emulate the ampache log levels
     */
    public const LOG_LEVEL_CRITICAL = 1;
    public const LOG_LEVEL_ERROR    = 2;
    public const LOG_LEVEL_WARNING  = 3;
    public const LOG_LEVEL_NOTICE   = 4;
    public const LOG_LEVEL_DEBUG    = 5;

    public const CONTEXT_TYPE = 'event_type';

    private const FALLBACK_USERNAME = 'ampache';
    private const LOG_NAME          = 'ampache';

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(
            static::LOG_LEVEL_CRITICAL,
            $message,
            $context
        );
    }

    public function alert($message, array $context = []): void
    {
        $this->log(
            static::LOG_LEVEL_CRITICAL,
            $message,
            $context
        );
    }

    public function critical($message, array $context = []): void
    {
        $this->log(
            static::LOG_LEVEL_CRITICAL,
            $message,
            $context
        );
    }

    public function error($message, array $context = []): void
    {
        $this->log(
            static::LOG_LEVEL_ERROR,
            $message,
            $context
        );
    }

    public function warning($message, array $context = []): void
    {
        $this->log(
            static::LOG_LEVEL_WARNING,
            $message,
            $context
        );
    }

    public function notice($message, array $context = []): void
    {
        $this->log(
            static::LOG_LEVEL_NOTICE,
            $message,
            $context
        );
    }

    public function info($message, array $context = []): void
    {
        $this->log(
            static::LOG_LEVEL_NOTICE,
            $message,
            $context
        );
    }

    public function debug($message, array $context = []): void
    {
        $this->log(
            static::LOG_LEVEL_DEBUG,
            $message,
            $context
        );
    }

    public function log($level, $message, array $context = []): void
    {
        if (!$this->configContainer->get('debug') || $level > $this->configContainer->get('debug_level')) {
            return;
        }

        $username = $context['username'] ?? null;

        if ($username === null || $username === '') {
            $user = Core::get_global('user');
            if ($user) {
                $username = $user->username;
            } else {
                $username = static::FALLBACK_USERNAME;
            }
        }

        /* Set it up here to make sure it's _always_ the same */
        $time       = time();
        $log_time   = date("c", $time);
        $event_name = $context[static::CONTEXT_TYPE] ?? '';

        $log_filename = $this->configContainer->get('log_filename');
        if (empty($log_filename)) {
            $log_filename = "%name.%Y%m%d.log";
        }

        $log_filename = str_replace("%name", static::LOG_NAME, $log_filename);
        $log_filename = str_replace("%Y", date('Y'), $log_filename);
        $log_filename = str_replace("%m", date('m'), $log_filename);
        $log_filename = str_replace("%d", date('d'), $log_filename);
        $log_filename = $this->configContainer->get('log_path') . '/' . $log_filename;
        $log_line     = "$log_time [$username] ($event_name) -> $message\n";

        // Do the deed
        $log_write = error_log($log_line, 3, $log_filename);

        if (!$log_write) {
            echo "Warning: Unable to write to log ($log_filename) Please check your log_path variable in ampache.cfg.php";
        }
    }
}
