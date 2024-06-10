<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\Init\Exception\ConfigFileNotFoundException;
use Ampache\Config\Init\Exception\ConfigFileNotParsableException;
use Ampache\Config\Init\Exception\DatabaseOutdatedException;
use Ampache\Config\Init\Exception\EnvironmentNotSuitableException;
use Ampache\Config\Init\Exception\GetTextNotAvailableException;
use Ampache\Module\System\Core;
use Ampache\Module\Util\EnvironmentInterface;

/**
 * This class performs the complete init process to build a working ampache application environment
 */
final class Init
{
    private EnvironmentInterface $environment;

    /** @var InitializationHandlerInterface[] */
    private array $initializationHandler;

    public function __construct(
        EnvironmentInterface $environment,
        array $initializationHandler
    ) {
        $this->environment           = $environment;
        $this->initializationHandler = $initializationHandler;
    }

    public function init(): void
    {
        $redirectionUrl = null;
        $e              = null;

        try {
            foreach ($this->initializationHandler as $initializationHandler) {
                $initializationHandler->init();
            }
        } catch (ConfigFileNotFoundException $e) {
            $redirectionUrl = 'install.php';
        } catch (ConfigFileNotParsableException $e) {
            $redirectionUrl = 'test.php?action=config';
        } catch (EnvironmentNotSuitableException | GetTextNotAvailableException $e) {
            $redirectionUrl = 'test.php';
        } catch (DatabaseOutdatedException $e) {
            $redirectionUrl = 'update.php';
        } finally {
            if ($e == null) {
                return;
            }
            if ($this->environment->isCli()) {
                throw $e;
            }
            $this->redirect((string)$redirectionUrl);
        }
    }

    private function redirect(string $destination): void
    {
        $this->environment->isSsl() ? $protocol = 'https' : $protocol = 'http';

        // Set up for redirection on important error cases
        $path = get_web_path();
        if (isset($_SERVER['HTTP_HOST'])) {
            $path = sprintf(
                '%s://%s%s',
                $protocol,
                Core::get_server('HTTP_HOST'),
                $path
            );
        }

        header(sprintf(
            'Location: %s/%s',
            $path,
            $destination
        ));

        die();
    }
}
