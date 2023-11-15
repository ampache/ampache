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
use Ampache\Module\Util\RequestParserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        RequestParserInterface $requestParser,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->requestParser   = $requestParser;
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Check to see if they've got an interface session or a valid API session
        if (
            !Session::exists('interface', $_COOKIE[$this->configContainer->getSessionName()]) &&
            !Session::exists('api', $_REQUEST['auth'] ?? '')
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
            !$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::STATISTICAL_GRAPHS) ||
            !is_dir(__DIR__ . '/../../../../vendor/szymach/c-pchart/src/Chart/')
        ) {
            $this->logger->warning(
                'Access denied, statistical graph disabled.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return null;
        }

        $object_type = $this->requestParser->getFromRequest('object_type');
        if (!InterfaceImplementationChecker::is_library_item($object_type)) {
            $object_type = null;
        }
        $graph     = new Graph();
        $user_id   = (int)$this->requestParser->getFromRequest('user_id');
        $object_id = (int)$this->requestParser->getFromRequest('object_id');
        $end_date  = (!empty($this->requestParser->getFromRequest('end_date')))
            ? (int)$this->requestParser->getFromRequest('start_date')
            : time();
        $start_date = (!empty($this->requestParser->getFromRequest('start_date')))
            ? (int)$this->requestParser->getFromRequest('end_date')
            : ($end_date - 864000);
        $zoom = (!empty($this->requestParser->getFromRequest('zoom')))
            ? $this->requestParser->getFromRequest('zoom')
            : 'day';
        $width = ((int)$this->requestParser->getFromRequest('width') !== 0)
            ? (int)$this->requestParser->getFromRequest('width')
            : 700;
        $height = ((int)$this->requestParser->getFromRequest('height') !== 0)
            ? (int)$this->requestParser->getFromRequest('height')
            : 260;

        $action_type = $this->requestParser->getFromRequest('type');
        switch ($action_type) {
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
