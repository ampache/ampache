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

namespace Ampache\Module\Application\Test;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\Install\TestConfigPageView;
use Ampache\Gui\Install\TestErrorPageView;
use Ampache\Gui\Install\TestPageView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\EnvironmentInterface;
use Exception;
use Gettext\Translations;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode\RFC\RFC7231;

final readonly class ConfigAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'config';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private ResponseFactoryInterface $responseFactory,
        private EnvironmentInterface $environment,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Check to see if the config file is working now, if so fall
        // through to the default, else show the appropriate template
        $configfile = __DIR__ . '/../../../../config/ampache.cfg.php';

        if ((parse_ini_file($configfile) ?: []) === []) {
            echo new TestConfigPageView()->render();

            return null;
        }

        if (!file_exists($configfile)) {
            return $this->responseFactory
                ->createResponse(RFC7231::FOUND)
                ->withHeader(
                    'Location',
                    '/install.php'
                );
        }

        // Make sure the config file is set up and parsable
        $results = (is_readable($configfile) && parse_ini_file($configfile))
            ? parse_ini_file($configfile)
            : false;
        if (!$results) {
            $link = __DIR__ . '/../../../../public/client/test.php?action=config';
        }

        if ($results !== [] && $results !== false) {
            /* Temp Fixes */
            $results = Preference::fix_preferences($results);
            $this->configContainer->updateConfig($results);
        }

        unset($results);
        // Try to load localization from cookie
        $session_name = $this->configContainer->getSessionName();

        if (isset($_COOKIE[$session_name . '_lang'])) {
            AmpConfig::set('lang', $_COOKIE[$session_name . '_lang']);
        }

        if (!class_exists(Translations::class)) {
            echo new TestErrorPageView()->render();
            throw new Exception('load_gettext()');
        }

        load_gettext();
        echo new TestPageView($this->environment, __DIR__ . '/../../../../config/ampache.cfg.php')->render();

        return null;
    }
}
