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

namespace Ampache\Config\Init;

use Ampache\Config\AmpConfig;
use Ampache\Config\Init\Exception\ConfigFileNotFoundException;
use Ampache\Config\Init\Exception\ConfigFileNotParsableException;
use Ampache\Config\Init\Exception\DatabaseOutdatedException;
use Ampache\Config\Init\Exception\EnvironmentNotSuitableException;
use Ampache\Config\Init\Exception\GetTextNotAvailableException;
use Ampache\Config\Init\Exception\RequireAuthException;
use Ampache\Module\System\Core;
use Ampache\Module\Util\EnvironmentInterface;

/**
 * This class performs the complete init process to build a working ampache application environment
 */
final readonly class Init
{
    public function __construct(
        private EnvironmentInterface $environment,
        /** @var InitializationHandlerInterface[] */
        private array $initializationHandler,
    ) {}

    public function init(): void
    {
        $redirectionUrl = null;
        $error          = null;

        try {
            foreach ($this->initializationHandler as $initializationHandler) {
                $initializationHandler->init();
            }
        } catch (ConfigFileNotFoundException $error) {
            $redirectionUrl = 'install.php';
        } catch (ConfigFileNotParsableException $error) {
            $redirectionUrl = 'test.php?action=config';
        } catch (EnvironmentNotSuitableException|GetTextNotAvailableException $error) {
            $redirectionUrl = 'test.php';
        } catch (DatabaseOutdatedException $error) {
            $redirectionUrl = 'update.php';
        } catch (RequireAuthException $error) {
            $redirectionUrl = 'login.php';
        }

        // returning from a finally block would discard an exception no catch above matched, so this stays outside one
        if ($error === null) {
            return;
        }

        if ($this->environment->isCli()) {
            throw $error;
        }

        $this->redirect((string) $redirectionUrl);
    }

    private function redirect(string $destination): void
    {
        $protocol = $this->environment->isSsl() ? 'https' : 'http';

        // Set up for redirection on important error cases. Prefer the configured web path:
        // get_web_path() is derived from the running script, so an entry point in a subdirectory
        // (public/m/index.php) would send you to <web_path>/m/login.php. It's empty when the config
        // hasn't loaded yet, which is exactly the install.php case, so fall back to the script path.
        $path = (string) AmpConfig::get('web_path', '');
        if ($path === '') {
            $path = get_web_path();
            if (isset($_SERVER['HTTP_HOST'])) {
                $path = sprintf(
                    '%s://%s%s',
                    $protocol,
                    Core::get_server('HTTP_HOST'),
                    $path
                );
            }
        }

        // Hand the requested page to the login form so it can send you back there afterwards.
        // Without this a pasted or bookmarked deep link always lands on index.php after logging in.
        if (
            $destination === 'login.php'
            && Core::get_server('REQUEST_METHOD') === 'GET'
            && !empty($_SERVER['REQUEST_URI'])
            && isset($_SERVER['HTTP_HOST'])
        ) {
            // REQUEST_URI is used raw here; Core::get_server() would htmlspecialchars the query
            // separators and break the url. It gets urlencoded now and validated against web_path
            // before it's used or displayed.
            $destination .= '?referrer=' . urlencode(sprintf(
                '%s://%s%s',
                $protocol,
                Core::get_server('HTTP_HOST'),
                (string) $_SERVER['REQUEST_URI']
            ));
        }

        header(sprintf(
            'Location: %s/%s',
            $path,
            $destination
        ));

        die();
    }
}
