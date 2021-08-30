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

namespace Ampache\Module\Application\StatisticGraph;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Module\Util\Graph;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Check to see if they've got an interface session or a valid API session
        if (
            !Session::exists('interface', $_COOKIE[$this->configContainer->getSessionName()]) &&
            !Session::exists('api', $_REQUEST['auth'])
        ) {
            $this->logger->warning(
                sprintf(
                    'Access denied, checked cookie session:%s and auth:%s',
                    $_COOKIE[$this->configContainer->getSessionName()], $_REQUEST['auth']
                ),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return null;
        }

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::STATISTICAL_GRAPHS) ||
            !is_dir(__DIR__ . '/../../../../vendor/szymach/c-pchart/src/Chart/')
        ) {
            $this->logger->warning(
                'Access denied, statistical graph disabled.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return null;
        }

        $type = $_REQUEST['type'];

        $user_id     = (int) ($_REQUEST['user_id']);
        $object_type = (string) scrub_in($_REQUEST['object_type']);
        if (!InterfaceImplementationChecker::is_library_item($object_type)) {
            $object_type = null;
        }
        $object_id  = (int) ($_REQUEST['object_id']);
        $start_date = (int) scrub_in($_REQUEST['start_date']);
        $end_date   = (int) scrub_in($_REQUEST['end_date']);
        $zoom       = (string) scrub_in($_REQUEST['zoom']);

        $width  = (int) ($_REQUEST['width']);
        $height = (int) ($_REQUEST['height']);

        $graph = new Graph();

        switch ($type) {
            case 'user_hits':
                $graph->render_user_hits($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, $width, $height);
                break;
            case 'user_bandwidth':
                $graph->render_user_bandwidth($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, $width, $height);
                break;
            case 'catalog_files':
                $graph->render_catalog_files($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, $width, $height);
                break;
            case 'catalog_size':
                $graph->render_catalog_size($user_id, $object_type, $object_id, $start_date, $end_date, $zoom, $width, $height);
                break;
        }

        return null;
    }
}
