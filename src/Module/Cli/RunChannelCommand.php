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

declare(strict_types=1);

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Channel\ChannelRunnerInterface;

final class RunChannelCommand extends Command
{
    private ConfigContainerInterface $configContainer;

    private ChannelRunnerInterface $channelRunner;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ChannelRunnerInterface $channelRunner
    ) {
        parent::__construct('run:channel', 'Runs a channel');

        $this->configContainer = $configContainer;
        $this->channelRunner   = $channelRunner;

        $this
            ->argument('<channel>', 'The id of the channel to run')
            ->argument('[port]', 'An optional port number')
            ->usage('<bold>  run:channel</end> <comment><channel> [port]</end> ## Runs the channel<eol/>');
    }

    public function execute(
        int $channel,
        ?int $port = null
    ): void {
        $io = $this->app()->io();

        if (!$this->configContainer->get('channel')) {
            $io->error('Cannot start channel, enable channels in your server config first.', true);

            return;
        }
        // Transcode is mandatory to have consistent stream codec
        $transcode_cfg = $this->configContainer->get('transcode');

        if ($transcode_cfg == 'never') {
            $io->error('Cannot start channel, transcoding is mandatory to work', true);

            return;
        }

        $this->channelRunner->run(
            $io,
            $channel,
            $port
        );
    }
}
