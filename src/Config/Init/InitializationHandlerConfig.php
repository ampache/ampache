<?php

declare(strict_types=1);

/**
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

namespace Ampache\Config\Init;

use Ampache\Config\AmpConfig;
use Ampache\Config\Init\Exception\ConfigFileNotFoundException;
use Ampache\Config\Init\Exception\ConfigFileNotParsableException;
use Ampache\Repository\Model\Preference;
use Ampache\Module\Util\EnvironmentInterface;
use DateTimeZone;

final class InitializationHandlerConfig implements InitializationHandlerInterface
{
    private const VERSION        = '7.0.0'; // AMPACHE_VERSION
    private const CONFIG_VERSION = '70';
    private const STRUCTURE      = 'public';  // Project release is using either the public html folder or squashed structure

    public const CONFIG_FILE_PATH = __DIR__ . '/../../../config/ampache.cfg.php';

    private EnvironmentInterface $environment;

    public function __construct(
        EnvironmentInterface $environment
    ) {
        $this->environment = $environment;
    }

    public function init(): void
    {
        // Check to make sure the config file exists. If it doesn't then go ahead and
        if (file_exists(static::CONFIG_FILE_PATH) === false) {
            throw new ConfigFileNotFoundException();
        }

        // Make sure the config file is set up and parsable
        $results = parse_ini_file(static::CONFIG_FILE_PATH);

        if ($results === false) {
            throw new ConfigFileNotParsableException();
        }

        /** This is the version.... fluff nothing more... */
        $results['version']            = static::VERSION;
        $results['int_config_version'] = static::CONFIG_VERSION;
        $results['structure']          = static::STRUCTURE;
        $results['phpversion']         = substr(phpversion(), 0, 3);

        $protocol = (make_bool($results['force_ssl'] ?? false) || $this->environment->isSsl() === true)
            ? 'https'
            : 'http';

        if ($this->environment->isCli() === false) {
            if (empty($results['http_host'])) {
                $results['http_host'] = $_SERVER['SERVER_NAME'];
            }
            if (empty($results['local_web_path'])) {
                $results['local_web_path'] = sprintf(
                    '%s://%s:%d%s',
                    $protocol,
                    $_SERVER['SERVER_NAME'],
                    $_SERVER['SERVER_PORT'],
                    $results['web_path'] ?? ''
                );
            }
            $results['http_port'] = (!empty($results['http_port']))
                ? $results['http_port']
                : $this->environment->getHttpPort();

            $port = ($results['http_port'] != 80 && $results['http_port'] != 443)
                ? ':' . $results['http_port']
                : '';

            $results['raw_web_path'] = empty($results['web_path']) ? '/' : $results['web_path'];
            $results['web_path']     = sprintf(
                '%s://%s%s%s',
                $protocol,
                $results['http_host'],
                $port,
                $results['web_path'] ?? ''
            );
            $results['site_charset'] = $results['site_charset'] ?? 'UTF-8';
            if (!isset($results['max_upload_size'])) {
                $results['max_upload_size'] = 1048576;
            }
            $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? '';
            if (isset($results['user_ip_cardinality']) && !$results['user_ip_cardinality']) {
                $results['user_ip_cardinality'] = 42;
            }

            // Variables needed for Auth class
            $results['cookie_path']   = $results['raw_web_path'];
            $results['cookie_domain'] = $results['http_host'];
            $results['cookie_life']   = $results['session_cookielife'];
            $results['cookie_secure'] = $results['session_cookiesecure'];
        }
        if (empty($results['date_timezone'])) {
            // fallback to the server timezone if not set
            $results['date_timezone'] = date_default_timezone_get();
        }
        if (!empty($results['date_timezone'])) {
            $listIdentifiers = DateTimeZone::listIdentifiers() ?? array();
            if (!empty($listIdentifiers) && !in_array($results['date_timezone'], $listIdentifiers)) {
                $results['date_timezone'] = "UTC";
            }
        }

        // Temp Fixes
        $results = Preference::fix_preferences($results);

        AmpConfig::set_by_array($results, true);
    }
}
