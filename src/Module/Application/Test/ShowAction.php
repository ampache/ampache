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

declare(strict_types=0);

namespace Ampache\Module\Application\Test;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ConfigContainerInterface $configContainer;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->configContainer = $configContainer;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param ServerRequestInterface $request
     * @param GuiGatekeeperInterface $gatekeeper
     * @return ResponseInterface|null
     * @throws Exception
     */
    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $configfile = __DIR__ . '/../../../../config/ampache.cfg.php';

        // Load config from file
        $results = array();
        if (!file_exists($configfile)) {
            return $this->responseFactory
                ->createResponse(StatusCode::FOUND)
                ->withHeader(
                    'Location',
                    '/install.php'
                );
        } else {
            // Make sure the config file is set up and parsable
            $results = (is_readable($configfile)) ? parse_ini_file($configfile) : '';

            if (empty($results)) {
                $link = __DIR__ . '/../../public/test.php?action=config';
            }
        }
        /* Temp Fixes */
        $results = Preference::fix_preferences($results);

        $this->configContainer->updateConfig($results);
        unset($results);

        // Try to load localization from cookie
        $session_name = $this->configContainer->getSessionName();

        if (isset($_COOKIE[$session_name . '_lang'])) {
            AmpConfig::set('lang', $_COOKIE[$session_name . '_lang']);
        }
        if (!class_exists('Gettext\Translations')) {
            require_once __DIR__ . '/../../../../public/templates/test_error_page.inc.php';
            throw new Exception('load_gettext()');
        } else {
            load_gettext();
            // Load template
            require_once __DIR__ . '/../../../../public/templates/show_test.inc.php';
        }

        return null;
    }
}
