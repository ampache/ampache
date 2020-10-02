<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
        try {
            foreach ($this->initializationHandler as $initializationHandler) {
                $initializationHandler->init();
            }
        } catch (ConfigFileNotFoundException $e) {
            $this->redirect('install.php');
        } catch (ConfigFileNotParsableException $e) {
            $this->redirect('test.php?action=config');
        } catch (EnvironmentNotSuitableException | GetTextNotAvailableException $e) {
            $this->redirect('test.php');
        } catch (DatabaseOutdatedException $e) {
            $this->redirect('update.php');
        }
    }

    private function redirect(string $destination): void
    {
        $this->environment->isSsl() ? $protocol = 'https' : $protocol = 'http';

        // Set up for redirection on important error cases
        $path = get_web_path();
        if (filter_has_var(INPUT_SERVER, 'HTTP_HOST')) {
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
