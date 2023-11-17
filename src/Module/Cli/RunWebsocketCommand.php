<?php

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

declare(strict_types=1);

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Broadcast\WebSocketFactoryInterface;

final class RunWebsocketCommand extends Command
{
    private const DEFAULT_PORT = 8100;

    private ConfigContainerInterface $configContainer;

    private WebSocketFactoryInterface $webSocketFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        WebSocketFactoryInterface $webSocketFactory
    ) {
        parent::__construct('run:websocket', T_('Run a Websocket'));

        $this->configContainer  = $configContainer;
        $this->webSocketFactory = $webSocketFactory;

        $this
            ->option('-p|--port', T_('Listening port, default 8100'), 'intval', static::DEFAULT_PORT)
            ->usage('<bold>  run:websocket</end> <comment>-p 8888</end> ## ' . T_('Run the websocket on port 8888') . '<eol/>');
    }

    public function execute(): void
    {
        $verbose = (bool) ($this->values()['verbosity'] ?? false);
        $port    = $this->values()['port'];

        $urlinfo = parse_url($this->configContainer->get('websocket_address') ?? '');
        $host    = $urlinfo['host'];
        if (empty($host)) {
            $host = 'localhost';
        }

        $this->io()->info(
            sprintf('Starting socket at %s:%d', $host, $port),
            true
        );

        $app               = $this->webSocketFactory->createApp($host, $port, '0.0.0.0');
        $brserver          = $this->webSocketFactory->createBroadcastServer();
        $brserver->verbose = $verbose;
        $app->route('/broadcast', $brserver);
        $app->route('/echo', $this->webSocketFactory->createEchoServer(), ['*']);
        $app->run();
    }
}
